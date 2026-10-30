<?php
declare(strict_types=1);

namespace QuarterPole\Race;

use QuarterPole\Db;
use QuarterPole\GameClock;
use QuarterPole\Ledger;
use QuarterPole\Settings;
use QuarterPole\UserError;

// Entering and scratching horses, and putting in claims.
final class Entries
{
    public function __construct(private Db $db, private Settings $settings) {}

    /** Why a horse can't run in a race, or null if it can. Age counts on the race's game date. */
    public static function ineligible(array $race, array $h): ?string
    {
        if ($h['status'] !== 'active') {
            return 'only active horses can race';
        }
        $age = GameClock::ageOn($h['birth_game_date'], new \DateTimeImmutable($race['game_date']));
        if ($age < (int) $race['age_min'] || ($race['age_max'] !== null && $age > (int) $race['age_max'])) {
            return 'wrong age';
        }
        if ($race['sex_rule'] === 'fillies_mares' && $h['sex'] !== 'F') {
            return 'fillies and mares only';
        }
        if ($race['sex_rule'] === 'colts_geldings' && $h['sex'] !== 'M') {
            return 'colts and geldings only';
        }
        if ($race['race_level'] === 'maiden' && (int) $h['wins'] > 0) {
            return 'has already won';
        }
        if ($race['homebred_only'] && $h['origin'] === 'foundation') {
            return 'homebreds only';
        }
        return null;
    }

    public static function raceLabel(array $race): string
    {
        $level = ['maiden' => 'Maiden', 'claiming' => 'Claiming', 'allowance' => 'Allowance',
                  'g3' => 'Grade 3', 'g2' => 'Grade 2', 'g1' => 'Grade 1'][$race['race_level']];
        if ($race['race_level'] === 'claiming') {
            $level .= ' (' . number_format((int) $race['claiming_price']) . ')';
        }
        return $level;
    }

    public static function conditions(array $race): string
    {
        $age = $race['age_max'] === null ? $race['age_min'] . 'yo and up'
            : ($race['age_min'] === $race['age_max'] ? $race['age_min'] . 'yo' : $race['age_min'] . ' to ' . $race['age_max'] . 'yo');
        $sex = ['open' => '', 'fillies_mares' => ', fillies and mares', 'colts_geldings' => ', colts and geldings'][$race['sex_rule']];
        return $age . $sex;
    }

    /** The entry fee this stable pays (Premium gets its discount). */
    public function fee(array $race, array $stable): int
    {
        $fee = (int) $race['entry_fee'];
        if ($stable['premium_until'] !== null && $stable['premium_until'] > date('Y-m-d H:i:s')) {
            $fee = (int) round($fee * (100 - $this->settings->int('premium_discount_pct')) / 100);
        }
        return $fee;
    }

    /** The player's horses that could be entered, with the reason for any that can't. */
    public function candidates(int $memberId, array $race): array
    {
        $horses = $this->db->all(
            "SELECT h.*, (SELECT r.id FROM qp_entries e JOIN qp_races r ON r.id = e.race_id
                           WHERE e.horse_id = h.id AND r.run_on = ? AND r.status IN ('open','closed') LIMIT 1) AS busy_race
               FROM qp_horses h WHERE h.owner_id = ? AND h.status = 'active' ORDER BY h.name",
            [$race['run_on'], $memberId]
        );
        foreach ($horses as &$h) {
            $h['why_not'] = self::ineligible($race, $h) ?? ($h['busy_race'] ? 'already entered that day' : null);
        }
        return $horses;
    }

    public function enter(int $memberId, int $raceId, int $horseId, string $realToday): string
    {
        return $this->db->transaction(function (Db $db) use ($memberId, $raceId, $horseId, $realToday) {
            $race = $db->one('SELECT * FROM qp_races WHERE id = ? FOR UPDATE', [$raceId]);
            if (!$race || $race['status'] !== 'open' || $race['run_on'] < $realToday) {
                throw new UserError('Entries for that race are closed.');
            }
            $h = $db->one('SELECT * FROM qp_horses WHERE id = ? FOR UPDATE', [$horseId]);
            if (!$h || (int) $h['owner_id'] !== $memberId) {
                throw new UserError('That is not your horse.');
            }
            if ($why = self::ineligible($race, $h)) {
                throw new UserError($h['name'] . " can't run in this race: $why.");
            }
            if ($db->value("SELECT 1 FROM qp_entries e JOIN qp_races r ON r.id = e.race_id
                             WHERE e.horse_id = ? AND r.run_on = ? AND r.status IN ('open','closed')", [$horseId, $race['run_on']])) {
                throw new UserError($h['name'] . ' is already entered in a race that day.');
            }
            if ((int) $db->value('SELECT COUNT(*) FROM qp_entries WHERE race_id = ?', [$raceId]) >= $this->settings->int('max_field_size')) {
                throw new UserError('That race is full.');
            }
            $stable = $db->one('SELECT * FROM qp_stables WHERE member_id = ?', [$memberId]);
            $fee = $this->fee($race, $stable);
            $jockey = (int) $db->value('SELECT id FROM qp_jockeys ORDER BY RAND() LIMIT 1');
            $db->run('INSERT INTO qp_entries (race_id, horse_id, owner_id, jockey_id, fee_paid) VALUES (?, ?, ?, ?, ?)',
                [$raceId, $horseId, $memberId, $jockey, $fee]);
            if ($fee > 0) {
                (new Ledger($db))->move($memberId, -$fee, 'entry_fee', 'qp_races', $raceId, 'Entry: ' . $h['name']);
            }
            return $h['name'] . ' is entered. Entry fee ' . number_format($fee) . ' Cash.';
        });
    }

    /** Takes a horse out before the race; the entry fee and any claims on it are refunded. */
    public function scratch(int $memberId, int $entryId, string $realToday): string
    {
        return $this->db->transaction(function (Db $db) use ($memberId, $entryId, $realToday) {
            $e = $db->one(
                'SELECT e.*, r.status, r.run_on, h.name FROM qp_entries e JOIN qp_races r ON r.id = e.race_id
                   JOIN qp_horses h ON h.id = e.horse_id WHERE e.id = ? FOR UPDATE',
                [$entryId]
            );
            if (!$e || (int) $e['owner_id'] !== $memberId) {
                throw new UserError('That is not your entry.');
            }
            if ($e['status'] !== 'open' || $e['run_on'] < $realToday) {
                throw new UserError('It is too late to scratch.');
            }
            $ledger = new Ledger($db);
            $paid = (int) $e['fee_paid'];
            if ($paid > 0) {
                $ledger->move($memberId, $paid, 'refund', 'qp_races', (int) $e['race_id'], 'Scratched: ' . $e['name']);
            }
            $this->refundClaims($db, $entryId, 'Scratched');
            $db->run('DELETE FROM qp_claims WHERE entry_id = ?', [$entryId]);
            $db->run('DELETE FROM qp_entries WHERE id = ?', [$entryId]);
            return $e['name'] . ' is scratched and the entry fee refunded.';
        });
    }

    /** Puts in a claim: the claiming price is held until the race is run. */
    public function claim(int $memberId, int $entryId, string $realToday): string
    {
        return $this->db->transaction(function (Db $db) use ($memberId, $entryId, $realToday) {
            $e = $db->one(
                'SELECT e.*, r.status, r.run_on, r.race_level, r.claiming_price, h.name FROM qp_entries e
                   JOIN qp_races r ON r.id = e.race_id JOIN qp_horses h ON h.id = e.horse_id WHERE e.id = ? FOR UPDATE',
                [$entryId]
            );
            if (!$e || $e['race_level'] !== 'claiming' || $e['owner_id'] === null) {
                throw new UserError('That horse can’t be claimed.');
            }
            if ($e['status'] !== 'open' || $e['run_on'] < $realToday) {
                throw new UserError('Claims for that race are closed.');
            }
            if ((int) $e['owner_id'] === $memberId) {
                throw new UserError('You can’t claim your own horse.');
            }
            if ($db->value('SELECT 1 FROM qp_claims WHERE entry_id = ? AND member_id = ?', [$entryId, $memberId])) {
                throw new UserError('You already have a claim in on ' . $e['name'] . '.');
            }
            $price = (int) $e['claiming_price'];
            $db->run('INSERT INTO qp_claims (entry_id, member_id, amount) VALUES (?, ?, ?)', [$entryId, $memberId, $price]);
            (new Ledger($db))->move($memberId, -$price, 'claim_out', 'qp_claims', $db->lastId(), 'Claim held: ' . $e['name']);
            return 'Your claim on ' . $e['name'] . ' is in. ' . number_format($price)
                . ' Cash is held until the race; if another stable also claims, a shake decides.';
        });
    }

    /** Call inside a transaction. Refunds every pending claim on an entry. */
    public function refundClaims(Db $db, int $entryId, string $why, ?int $exceptClaim = null): void
    {
        $ledger = new Ledger($db);
        foreach ($db->all("SELECT c.*, h.name FROM qp_claims c JOIN qp_entries e ON e.id = c.entry_id
                            JOIN qp_horses h ON h.id = e.horse_id WHERE c.entry_id = ? AND c.status = 'pending'", [$entryId]) as $c) {
            if ((int) $c['id'] === $exceptClaim) {
                continue;
            }
            $ledger->move((int) $c['member_id'], (int) $c['amount'], 'refund', 'qp_claims', (int) $c['id'], "$why: claim on {$c['name']} refunded");
            $db->run("UPDATE qp_claims SET status = ? WHERE id = ?", [$exceptClaim ? 'lost' : 'refunded', $c['id']]);
        }
    }
}
