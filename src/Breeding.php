<?php
declare(strict_types=1);

namespace QuarterPole;

use QuarterPole\Genetics\Inheritance;

// Booking a mare to a stallion: the Proposed breed page and the breeding itself.
final class Breeding
{
    public function __construct(private Db $db, private Settings $settings, private GameClock $clock) {}

    /** Stallions standing at stud (player and game), with how many mares they have left this game year. */
    public function studs(?string $surface = null): array
    {
        $year = $this->clock->year();
        $sql = "SELECT h.*, s.stable_name AS owner_name,
                       (SELECT COUNT(*) FROM qp_breedings b WHERE b.sire_id = h.id AND b.bred_in_game_year = ?) AS booked
                  FROM qp_horses h LEFT JOIN qp_stables s ON s.member_id = h.owner_id
                 WHERE h.sex = 'M' AND h.gelded = 0 AND h.status = 'retired' AND h.stud_fee IS NOT NULL";
        $params = [$year];
        if ($surface) {
            $sql .= ' AND h.surface_pref = ?';
            $params[] = $surface;
        }
        return $this->db->all($sql . ' ORDER BY h.stud_fee DESC, h.name', $params);
    }

    /** Mares this stable can breed (retired to breeding, old enough). */
    public function mares(int $memberId): array
    {
        $minAge = $this->settings->int('mare_breeding_age');
        $year = (int) $this->clock->today()->format('Y');
        return $this->db->all(
            "SELECT * FROM qp_horses WHERE owner_id = ? AND sex = 'F' AND status = 'retired'
               AND ? - YEAR(birth_game_date) >= ? ORDER BY name",
            [$memberId, $year, $minAge]
        );
    }

    /** Birth years still open for this pair: this year, plus earlier years (backdated foals). */
    public function openYears(array $sire, array $dam): array
    {
        $now = (int) $this->clock->today()->format('Y');
        // Bred at breeding age, born the next year.
        $first = max(
            (int) substr($dam['birth_game_date'], 0, 4) + $this->settings->int('mare_breeding_age') + 1,
            (int) substr($sire['birth_game_date'], 0, 4) + $this->settings->int('stallion_breeding_age') + 1,
        );
        $taken = array_map('intval', array_column(
            $this->db->all('SELECT foal_game_year FROM qp_breedings WHERE dam_id = ?', [$dam['id']]),
            'foal_game_year'
        ));
        $years = [];
        for ($y = $now; $y >= $first; $y--) {
            $gameYear = $y - GameClock::YEAR_OFFSET;
            if (!in_array($gameYear, $taken, true)) {
                $years[] = $gameYear;
            }
        }
        return $years;
    }

    /** Everything the Proposed breed page shows. */
    public function proposal(int $memberId, array $sire, array $dam): array
    {
        $sameOwner = $sire['owner_id'] !== null && (int) $sire['owner_id'] === $memberId;
        $fee = $sameOwner ? 0 : (int) $sire['stud_fee'];
        if ($sire['origin'] === 'game_stallion' && $this->isPremium($memberId)) {
            $fee = (int) round($fee * (100 - $this->settings->int('premium_discount_pct')) / 100);
        }
        $coi = (new Pedigree($this->db))->coi((int) $sire['id'], (int) $dam['id']);
        $bothTested = $sire['genetics_tested'] && $dam['genetics_tested'];
        return [
            'fee'          => $fee,
            'coi'          => $coi,
            'dud_pct'      => (new Foal($this->settings))->dudChance($coi),
            'sire_share'   => Foal::sireShare($sire, $dam),
            'lethal'       => $bothTested ? Inheritance::lethalChance($sire, $dam) : null,
            'both_tested'  => (bool) $bothTested,
            'years'        => $this->openYears($sire, $dam),
            'book_full'    => $this->bookFull($sire),
            'same_owner'   => $sameOwner,
        ];
    }

    /**
     * Books the breeding, charges the fee and creates the foal.
     * @return array{foal_id:?int, born_dead:bool}
     */
    public function breed(int $memberId, int $sireId, int $damId, int $foalGameYear, string $foalName): array
    {
        return $this->db->transaction(function (Db $db) use ($memberId, $sireId, $damId, $foalGameYear, $foalName) {
            $sire = $db->one('SELECT * FROM qp_horses WHERE id = ? FOR UPDATE', [$sireId]);
            $dam  = $db->one('SELECT * FROM qp_horses WHERE id = ? FOR UPDATE', [$damId]);
            $this->check($memberId, $sire, $dam);

            $p = $this->proposal($memberId, $sire, $dam);
            if (!in_array($foalGameYear, $p['years'], true)) {
                throw new UserError('That mare cannot have a foal in that year.');
            }
            if ($p['book_full']) {
                throw new UserError($sire['name'] . "'s book is full for this year.");
            }
            $foalName = Horse::validateName($db, $foalName);

            $ledger = new Ledger($db);
            if ($p['fee'] > 0) {
                $ledger->move($memberId, -$p['fee'], 'stud_fee_out', 'qp_horses', $sireId, 'Stud fee: ' . $sire['name']);
                if ($sire['owner_id'] !== null) {
                    $ledger->move((int) $sire['owner_id'], $p['fee'], 'stud_fee_in', 'qp_horses', $sireId, 'Stud fee from ' . $dam['name']);
                }
            }

            $genes = Inheritance::cross($sire, $dam);
            $bornDead = Inheritance::isLethal($genes);
            $currentYear = $this->clock->year();
            $foalId = null;

            if (!$bornDead) {
                $row = (new Foal($this->settings))->roll($sire, $dam, $p['coi']) + Foal::colorFields($genes);
                $row += [
                    'name'            => $foalName,
                    'sex'             => mt_rand(0, 1) ? 'M' : 'F',
                    'birth_game_date' => ($foalGameYear + GameClock::YEAR_OFFSET) . '-01-01',
                    'sire_id'         => $sireId,
                    'dam_id'          => $damId,
                    'owner_id'        => $memberId,
                    'breeder_id'      => $memberId,
                    'origin'          => $foalGameYear < $currentYear ? 'backdated' : 'bred',
                    'report_level'    => 'none',
                ];
                $age = $currentYear - $foalGameYear;
                if ($age >= 2) {
                    $row['report_level'] = 'basic';
                }
                $cols = array_keys($row);
                $db->run(
                    'INSERT INTO qp_horses (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')',
                    array_values($row)
                );
                $foalId = $db->lastId();
                if ($age >= 2) {
                    $db->run("INSERT INTO qp_trainer_reports (horse_id, level, notes) VALUES (?, 'basic', ?)", [$foalId, TrainerNotes::write($row, 'basic')]);
                }
            }

            $db->run(
                'INSERT INTO qp_breedings (sire_id, dam_id, dam_owner_id, stud_fee_paid, backdated, foal_game_year, bred_in_game_year, foal_id, outcome)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$sireId, $damId, $memberId, $p['fee'], (int) ($foalGameYear < $currentYear), $foalGameYear, $currentYear, $foalId, $bornDead ? 'born_dead' : 'live']
            );
            return ['foal_id' => $foalId, 'born_dead' => $bornDead];
        });
    }

    /** Throws a UserError if this pair cannot be bred by this player. */
    public function check(int $memberId, ?array $sire, ?array $dam): void
    {
        if (!$dam || (int) $dam['owner_id'] !== $memberId || $dam['sex'] !== 'F') {
            throw new UserError('Pick one of your own mares.');
        }
        if ($dam['status'] !== 'retired') {
            throw new UserError('A mare must be retired to breeding before she can be bred.');
        }
        if (!$sire || $sire['sex'] !== 'M' || $sire['gelded'] || $sire['status'] !== 'retired') {
            throw new UserError('That stallion is not available for breeding.');
        }
        $own = $sire['owner_id'] !== null && (int) $sire['owner_id'] === $memberId;
        if (!$own && $sire['stud_fee'] === null) {
            throw new UserError('That stallion is not standing at stud.');
        }
    }

    private function bookFull(array $sire): bool
    {
        $cap = $sire['origin'] === 'game_stallion'
            ? $this->settings->int('book_cap_game')
            : min($this->settings->int('book_cap_player'), (int) ($sire['book_size'] ?? $this->settings->int('book_cap_player')));
        $booked = (int) $this->db->value(
            'SELECT COUNT(*) FROM qp_breedings WHERE sire_id = ? AND bred_in_game_year = ?',
            [$sire['id'], $this->clock->year()]
        );
        return $booked >= $cap;
    }

    private function isPremium(int $memberId): bool
    {
        $until = $this->db->value('SELECT premium_until FROM qp_stables WHERE member_id = ?', [$memberId]);
        return $until !== null && strtotime((string) $until) > time();
    }
}
