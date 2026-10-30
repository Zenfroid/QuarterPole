<?php
declare(strict_types=1);

namespace QuarterPole\Race;

use QuarterPole\Ami;
use QuarterPole\Db;
use QuarterPole\GameClock;
use QuarterPole\HorseFactory;
use QuarterPole\Ledger;
use QuarterPole\Maturity;
use QuarterPole\Names;
use QuarterPole\Settings;

// Runs the day's races: ghosts fill short fields, the engine runs, then purses,
// AMI, energy and claims are settled. Races with no player horses are cancelled.
final class Runner
{
    private const RATED = ['break', 'sprint', 'speed', 'dexterity', 'temper', 'drive', 'track', 'scope'];

    public function __construct(private Db $db, private Settings $settings, private GameClock $clock) {}

    /** @return array{run:int, cancelled:int} */
    public function runDue(string $realToday): array
    {
        $out = ['run' => 0, 'cancelled' => 0];
        foreach ($this->db->all("SELECT id FROM qp_races WHERE status = 'open' AND run_on <= ? ORDER BY run_on, id", [$realToday]) as $r) {
            $out[$this->runRace((int) $r['id']) ? 'run' : 'cancelled']++;
        }
        return $out;
    }

    /** @return bool false if the race was cancelled */
    public function runRace(int $raceId): bool
    {
        return $this->db->transaction(function (Db $db) use ($raceId) {
            $race = $db->one('SELECT * FROM qp_races WHERE id = ? FOR UPDATE', [$raceId]);
            if ($race['status'] !== 'open') {
                return $race['status'] === 'run';
            }
            if (!$db->value('SELECT 1 FROM qp_entries WHERE race_id = ? AND owner_id IS NOT NULL', [$raceId])) {
                $db->run("UPDATE qp_races SET status = 'cancelled' WHERE id = ?", [$raceId]);
                return false;
            }
            $track = $db->one('SELECT * FROM qp_tracks WHERE id = ?', [$race['track_id']]);
            $this->addGhosts($race);

            $entries = $db->all(
                'SELECT e.id AS entry_id, e.owner_id, e.jockey_id, j.skill AS jockey_skill, h.*
                   FROM qp_entries e JOIN qp_horses h ON h.id = e.horse_id JOIN qp_jockeys j ON j.id = e.jockey_id
                  WHERE e.race_id = ?',
                [$raceId]
            );
            shuffle($entries);
            $runners = [];
            $gameDate = new \DateTimeImmutable($race['game_date']);
            $peakYears = $this->settings->int('peak_years');
            foreach ($entries as $i => $h) {
                $db->run('UPDATE qp_entries SET post_position = ? WHERE id = ?', [$i + 1, $h['entry_id']]);
                $f = Maturity::factor($h, GameClock::ageOn($h['birth_game_date'], $gameDate), Maturity::yearFraction($gameDate), $peakYears);
                $r = $h;
                foreach (self::RATED as $s) {
                    $r["stat_$s"] = (float) $h["stat_$s"] * $f;
                }
                $runners[(int) $h['entry_id']] = $r;
            }

            $race['track_condition'] = $this->condition($track, $race['surface']);
            $engine = new Engine([
                'noise'          => $this->settings->float('race_noise_pct') / 100,
                'form'           => $this->settings->float('race_form_pct') / 100,
                'off_surface'    => $this->settings->float('race_off_surface_pct') / 100,
                'stretch_effect' => $this->settings->float('race_stretch_effect_pct') / 100,
            ]);
            $result = $engine->run($race, $track, $runners);
            $this->record($race, $runners, $result);
            $this->settleClaims($race);
            $db->run("UPDATE qp_races SET status = 'run', track_condition = ? WHERE id = ?", [$race['track_condition'], $raceId]);
            if ($race['series_id'] !== null) {
                $this->checkSeries($race, $runners[$result['order'][0]]);
            }
            return true;
        });
    }

    /** Writes results, pays purses and AMI, and updates each horse's record and Energy. */
    private function record(array $race, array $runners, array $result): void
    {
        $split = $this->settings->intList('purse_split');
        $amiFinish = $this->settings->intList('ami_finish');
        $energyCost = min($this->settings->int('energy_race_max'),
            $this->settings->int('energy_race_base') + (int) round($this->settings->float('energy_race_per_furlong') * (float) $race['distance_f']));
        $ledger = new Ledger($this->db);
        $ami = new Ami($this->db, $this->settings);

        foreach ($result['finish'] as $entryId => $f) {
            $pos = $f['position'];
            $earn = isset($split[$pos - 1]) ? (int) round((int) $race['purse'] * $split[$pos - 1] / 100) : 0;
            $this->db->run(
                'UPDATE qp_entries SET finish_position = ?, margin_text = ?, final_time = ?, earnings = ? WHERE id = ?',
                [$pos, $pos === 1 ? null : Calls::margin($f['lengths']), $f['time'], $earn, $entryId]
            );
            foreach ($result['calls'] as $point => $snap) {
                if (isset($snap[$entryId])) {
                    $this->db->run(
                        'INSERT INTO qp_race_calls (entry_id, call_point, position, lengths_behind) VALUES (?, ?, ?, ?)',
                        [$entryId, $point, $snap[$entryId]['position'], min(999, $snap[$entryId]['lengths'])]
                    );
                }
            }
            $h = $runners[$entryId];
            $this->db->run(
                'UPDATE qp_horses SET starts = starts + 1, wins = wins + ?, places = places + ?, shows = shows + ?,
                        earnings = earnings + ?, energy = GREATEST(0, energy - ?) WHERE id = ?',
                [(int) ($pos === 1), (int) ($pos === 2), (int) ($pos === 3), $earn, $energyCost, $h['id']]
            );
            // Ghost purses show on the chart but are never paid.
            if ($h['owner_id'] !== null) {
                $owner = (int) $h['owner_id'];
                if ($earn > 0) {
                    $ledger->move($owner, $earn, 'purse', 'qp_races', (int) $race['id'], ordinal($pos) . ': ' . $h['name']);
                }
                $ami->award($owner, $this->settings->int('ami_race_entry'), 'race_entry', (int) $race['id']);
                $ami->award($owner, $amiFinish[$pos - 1] ?? 0, 'race_finish', (int) $race['id']);
            }
        }
        foreach ($result['fractions'] as $at => $time) {
            $this->db->run('INSERT INTO qp_race_fractions (race_id, at_f, time_s) VALUES (?, ?, ?)', [$race['id'], $at, round($time, 2)]);
        }
    }

    /** A horse that has now won every leg of a triple this game year earns its owner the badge. */
    private function checkSeries(array $race, array $winner): void
    {
        if ($winner['owner_id'] === null) {
            return;
        }
        $series = $this->db->one('SELECT * FROM qp_series WHERE id = ?', [$race['series_id']]);
        $legs = (int) $this->db->value('SELECT COUNT(DISTINCT series_leg) FROM qp_stakes WHERE series_id = ? AND active = 1', [$race['series_id']]);
        $won = (int) $this->db->value(
            "SELECT COUNT(DISTINCT r.series_leg) FROM qp_entries e JOIN qp_races r ON r.id = e.race_id
              WHERE e.horse_id = ? AND e.finish_position = 1 AND r.status = 'run' AND r.series_id = ? AND YEAR(r.game_date) = ?",
            [$winner['id'], $race['series_id'], (int) substr($race['game_date'], 0, 4)]
        );
        if ($series['kind'] === 'triple' && $legs > 0 && $won >= $legs) {
            $this->db->run('INSERT IGNORE INTO qp_badges (member_id, badge_code, tier) VALUES (?, ?, 1)', [$winner['owner_id'], $series['badge_code']]);
        }
    }

    /** One claim per claimed horse wins (a shake when several); the rest are refunded. */
    private function settleClaims(array $race): void
    {
        if ($race['race_level'] !== 'claiming') {
            return;
        }
        $entries = new Entries($this->db, $this->settings);
        $ledger = new Ledger($this->db);
        $claimed = $this->db->all(
            "SELECT DISTINCT e.id, e.horse_id, e.owner_id, h.name FROM qp_claims c JOIN qp_entries e ON e.id = c.entry_id
               JOIN qp_horses h ON h.id = e.horse_id WHERE e.race_id = ? AND c.status = 'pending'",
            [$race['id']]
        );
        foreach ($claimed as $e) {
            $win = $this->db->one("SELECT * FROM qp_claims WHERE entry_id = ? AND status = 'pending' ORDER BY RAND() LIMIT 1", [$e['id']]);
            $buyer = (int) $win['member_id'];
            $ledger->move((int) $e['owner_id'], (int) $win['amount'], 'claim_in', 'qp_claims', (int) $win['id'], 'Claimed: ' . $e['name']);
            $this->db->run("UPDATE qp_claims SET status = 'won' WHERE id = ?", [$win['id']]);
            $this->db->run('UPDATE qp_entries SET claimed_by = ? WHERE id = ?', [$buyer, $e['id']]);
            $this->db->run('UPDATE qp_horses SET owner_id = ?, stud_fee = NULL, offered_for_sale = 0 WHERE id = ?', [$buyer, $e['horse_id']]);
            $entries->refundClaims($this->db, (int) $e['id'], 'Lost the shake', (int) $win['id']);
        }
    }

    /** Fills the field up to min_field_size with ghost horses, reusing old ghosts first. */
    private function addGhosts(array $race): void
    {
        $have = (int) $this->db->value('SELECT COUNT(*) FROM qp_entries WHERE race_id = ?', [$race['id']]);
        $need = $this->settings->int('min_field_size') - $have;
        if ($need <= 0) {
            return;
        }
        $year = (int) substr($race['game_date'], 0, 4);
        $sql = "SELECT h.id FROM qp_horses h
                 WHERE h.origin = 'ghost' AND h.status = 'active' AND h.surface_pref = ?
                   AND YEAR(h.birth_game_date) BETWEEN ? AND ?
                   AND NOT EXISTS (SELECT 1 FROM qp_entries e JOIN qp_races r ON r.id = e.race_id
                                    WHERE e.horse_id = h.id AND r.run_on = ?)";
        $params = [$race['surface'], $year - (int) ($race['age_max'] ?? 12), $year - (int) $race['age_min'], $race['run_on']];
        if ($race['sex_rule'] === 'fillies_mares') {
            $sql .= " AND h.sex = 'F'";
        } elseif ($race['sex_rule'] === 'colts_geldings') {
            $sql .= " AND h.sex = 'M'";
        }
        if ($race['race_level'] === 'maiden') {
            $sql .= ' AND h.wins = 0';
        }
        $ids = array_column($this->db->all($sql . " ORDER BY RAND() LIMIT $need", $params), 'id');
        while (count($ids) < $need) {
            $ids[] = $this->makeGhost($race);
        }
        foreach ($ids as $id) {
            $jockey = (int) $this->db->value('SELECT id FROM qp_jockeys ORDER BY RAND() LIMIT 1');
            $this->db->run('INSERT INTO qp_entries (race_id, horse_id, owner_id, jockey_id) VALUES (?, ?, NULL, ?)', [$race['id'], $id, $jockey]);
        }
    }

    /** A computer-made horse: foundation stock with weaker stats, aged to fit the race. */
    private function makeGhost(array $race): int
    {
        $row = (new HorseFactory($this->db, $this->settings, $this->clock))->rollFoundation($race['surface']);
        $drop = $this->settings->int('ghost_stat_drop_max');
        foreach (HorseFactory::STATS as $s) {
            $row[$s] = max(25, $row[$s] - mt_rand(0, $drop));
        }
        // Ghosts run at their peak age so the weaker stats are what they race on.
        $maxAge = $race['age_max'] === null ? (int) $race['age_min'] + 3 : (int) $race['age_max'];
        $age = mt_rand((int) $race['age_min'], $maxAge);
        $row['peak_age'] = max(2, min(5, $age));
        $sex = match ($race['sex_rule']) {
            'fillies_mares' => 'F',
            'colts_geldings' => 'M',
            default => mt_rand(0, 1) ? 'M' : 'F',
        };
        $row += [
            'name'            => Names::unique($this->db),
            'sex'             => $sex,
            'gelded'          => (int) ($sex === 'M' && mt_rand(1, 100) <= 40),
            'birth_game_date' => ((int) substr($race['game_date'], 0, 4) - $age) . '-01-01',
            'owner_id'        => null,
            'origin'          => 'ghost',
            'report_level'    => 'none',
        ];
        $cols = array_keys($row);
        $this->db->run(
            'INSERT INTO qp_horses (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')',
            array_values($row)
        );
        return $this->db->lastId();
    }

    /** Track condition for the day, from the track's rain chance and profile. */
    private function condition(array $track, string $surface): string
    {
        $rain = mt_rand(1, 100) <= (int) $track['rain_chance_pct'];
        $dryGood = ['mostly_fast' => 20, 'varied' => 40, 'wet' => 50][$track['condition_profile']] ?? 40;
        if ($surface === 'dirt') {
            return $rain ? (mt_rand(0, 1) ? 'muddy' : 'sloppy') : (mt_rand(1, 100) <= $dryGood ? 'good' : 'fast');
        }
        return $rain ? (mt_rand(1, 100) <= 60 ? 'yielding' : 'soft') : (mt_rand(1, 100) <= $dryGood ? 'good' : 'firm');
    }

    /** Daily Energy recovery for every horse that can race. */
    public function recoverEnergy(): void
    {
        $this->db->run(
            "UPDATE qp_horses SET energy = LEAST(100, energy + GREATEST(1, ROUND(stat_recovery / ?)))
              WHERE status = 'active' AND energy < 100",
            [max(1, $this->settings->int('recovery_divisor'))]
        );
    }
}
