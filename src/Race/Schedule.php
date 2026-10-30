<?php
declare(strict_types=1);

namespace QuarterPole\Race;

use QuarterPole\Db;
use QuarterPole\GameClock;
use QuarterPole\Settings;

// Builds race cards for the coming real days from the weekly schedule.
// Stakes days run the named stakes due that day (qp_stakes), then unnamed graded
// stakes to fill the card.
final class Schedule
{
    // Real weekday (1 = Monday) => [track kind, race levels]
    private const WEEK = [
        1 => ['flat', ['claiming']],
        2 => ['flat', ['maiden', 'allowance']],
        4 => ['flat', ['maiden', 'allowance']],
        5 => ['steeplechase', ['maiden', 'claiming', 'allowance']],
        3 => ['flat', 'stakes'],
        6 => ['flat', 'stakes'],
        7 => ['steeplechase', 'stakes'],
    ];

    private const DISTANCES = [
        'dirt'         => [5, 5.5, 6, 6, 6.5, 7, 8, 8.5, 9, 10],
        'turf'         => [5, 5.5, 8, 8, 8.5, 9, 10, 11, 12],
        'steeplechase' => [16, 18, 20, 20, 22, 24],
    ];

    public function __construct(private Db $db, private Settings $settings, private GameClock $clock) {}

    /** Fills any missing race days from $realToday up to the schedule horizon. Returns races created. */
    public function fill(string $realToday): int
    {
        $start = new \DateTimeImmutable($realToday);
        $made = 0;
        for ($i = 0; $i <= $this->settings->int('schedule_days_ahead'); $i++) {
            $day = $start->modify("+$i day");
            if ($this->db->value('SELECT 1 FROM qp_races WHERE run_on = ? LIMIT 1', [$day->format('Y-m-d')])) {
                continue;
            }
            $made += $this->buildDay($day, $i);
        }
        return $made;
    }

    private function buildDay(\DateTimeImmutable $day, int $daysAhead): int
    {
        $plan = self::WEEK[(int) $day->format('N')] ?? null;
        if (!$plan) {
            return 0;
        }
        [$kind, $levels] = $plan;
        $gameDate = $this->clock->today()->modify('+' . ($daysAhead * $this->settings->int('game_speed')) . ' day')->format('Y-m-d');
        $tracks = $this->db->all(
            $kind === 'steeplechase'
                ? 'SELECT * FROM qp_tracks WHERE active = 1 AND has_steeplechase = 1'
                : 'SELECT * FROM qp_tracks WHERE active = 1 AND (has_dirt = 1 OR has_turf = 1)'
        );
        if ($levels === 'stakes') {
            return $this->buildStakesDay($day, $gameDate, $kind, $tracks);
        }
        $perCard = $this->settings->int('races_per_card');
        $made = 0;
        foreach ($tracks as $track) {
            for ($n = 1; $n <= $perCard; $n++) {
                $level = $levels[($n - 1) % count($levels)];
                $this->insert($this->design($track, $level, $kind), $track, $level, $n, $day->format('Y-m-d'), $gameDate);
                $made++;
            }
        }
        return $made;
    }

    /** Named stakes due on this day, then unnamed graded stakes up to the day's count. */
    private function buildStakesDay(\DateTimeImmutable $day, string $gameDate, string $kind, array $tracks): int
    {
        $weekday = ['3' => 'wed', '6' => 'sat', '7' => 'sun'][$day->format('N')];
        $g = new \DateTimeImmutable($gameDate);
        $year = (int) $g->format('Y');
        $made = 0;
        $hasG1 = false;
        $named = $this->db->all(
            'SELECT s.* FROM qp_stakes s JOIN qp_tracks t ON t.id = s.track_id WHERE s.active = 1 AND t.active = 1 AND s.weekday = ?',
            [$weekday]
        );
        foreach ($named as $st) {
            // Each weekday comes round every 14 game days, so exactly one race day falls in this window.
            $target = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $st['game_month'], min(28, (int) $st['game_day'])));
            if ($g < $target || $g > $target->modify('+13 day')) {
                continue;
            }
            if ($this->db->value('SELECT 1 FROM qp_races WHERE stakes_id = ? AND YEAR(game_date) = ?', [$st['id'], $year])) {
                continue;
            }
            $this->insertRace($st + ['stakes_id' => $st['id']], $day->format('Y-m-d'), $gameDate);
            $hasG1 = $hasG1 || $st['race_level'] === 'g1';
            $made++;
        }

        $want = $this->settings->int($kind === 'steeplechase' ? 'stakes_per_sunday' : 'stakes_per_flat_day');
        if (!$tracks) {
            return $made;
        }
        for ($n = $made; $n < $want; $n++) {
            if (!$hasG1 && mt_rand(1, 100) <= $this->settings->int('stakes_g1_day_pct')) {
                $level = 'g1';
                $hasG1 = true;
            } else {
                $level = mt_rand(1, 100) <= $this->settings->int('stakes_g2_pct') ? 'g2' : 'g3';
            }
            // Re-roll a few times so one day doesn't carry two races with the same plain name.
            for ($try = 0; $try < 5; $try++) {
                $track = $tracks[array_rand($tracks)];
                $c = $this->design($track, $level, $kind, (int) $g->format('n'));
                if (!$this->db->value('SELECT 1 FROM qp_races WHERE run_on = ? AND name = ?', [$day->format('Y-m-d'), self::stakesName($track, $c)])) {
                    break;
                }
            }
            [$lo, $hi] = $this->settings->intList("purse_$level");
            $c += [
                'track_id' => $track['id'], 'race_level' => $level, 'name' => self::stakesName($track, $c),
                'purse' => (int) round(mt_rand($lo, $hi) / 5000) * 5000,
            ];
            $this->insertRace($c, $day->format('Y-m-d'), $gameDate);
            $made++;
        }
        return $made;
    }

    /** A plain descriptive name for an unnamed stakes, e.g. "Riverside Juvenile Fillies Turf Mile". */
    private static function stakesName(array $track, array $c): string
    {
        $words = [preg_replace('/ (Downs|Park)$/', '', $track['name'])];
        if ($c['age_max'] === 2) {
            $words[] = 'Juvenile';
        }
        if ($c['sex_rule'] === 'fillies_mares') {
            $words[] = $c['age_max'] !== null && $c['age_max'] <= 3 ? 'Fillies' : 'Distaff';
        }
        $d = (float) $c['distance_f'];
        if ($c['surface'] === 'steeplechase') {
            $words[] = $d >= 26 ? 'Marathon Chase' : ($d >= 20 ? 'Chase' : 'Novice Chase');
        } else {
            if ($c['surface'] === 'turf') {
                $words[] = 'Turf';
            }
            $words[] = match (true) {
                $d <= 7  => 'Sprint',
                $d <= 8.5 => 'Mile',
                $d <= 10 => 'Classic',
                default  => 'Marathon',
            };
        }
        return implode(' ', $words) . ' Stakes';
    }

    /** Surface, distance, age and sex conditions for one race. */
    private function design(array $track, string $level, string $kind, int $gameMonth = 6): array
    {
        if ($kind === 'steeplechase') {
            $surface = 'steeplechase';
        } elseif ($track['has_turf'] && (!$track['has_dirt'] || mt_rand(1, 100) <= 35)) {
            $surface = 'turf';
        } else {
            $surface = 'dirt';
        }

        // Age groups: 2-year-olds, 3-year-olds, or 3 and up (steeplechase is 3 and up or 4 and up).
        $roll = mt_rand(1, 100);
        if ($surface === 'steeplechase') {
            [$min, $max] = in_array($level, ['g1', 'g2', 'g3'], true) || $roll > 50 ? [4, null] : [3, null];
        } elseif ($roll <= ($level === 'maiden' ? 35 : 20) && $gameMonth >= 5) {
            // Two-year-olds race from late spring, as in real racing.
            [$min, $max] = [2, 2];
        } elseif ($roll <= 55) {
            [$min, $max] = [3, 3];
        } else {
            [$min, $max] = [3, null];
        }
        $distances = self::DISTANCES[$surface];
        if ($max === 2) {
            $distances = array_values(array_filter($distances, fn ($d) => $d <= 8.5));
        }
        return [
            'surface' => $surface,
            'distance_f' => $distances[array_rand($distances)],
            'age_min' => $min,
            'age_max' => $max,
            'sex_rule' => mt_rand(1, 100) <= 25 ? 'fillies_mares' : 'open',
        ];
    }

    private function insertRace(array $c, string $runOn, string $gameDate): void
    {
        $this->db->run(
            'INSERT INTO qp_races (track_id, series_id, series_leg, stakes_id, name, race_level, surface, distance_f, age_min, age_max,
                                   sex_rule, homebred_only, claiming_price, purse, entry_fee, run_on, game_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$c['track_id'], $c['series_id'] ?? null, $c['series_leg'] ?? null, $c['stakes_id'] ?? null, $c['name'], $c['race_level'],
             $c['surface'], $c['distance_f'], $c['age_min'], $c['age_max'], $c['sex_rule'], (int) ($c['homebred_only'] ?? 0),
             $c['claiming_price'] ?? null, $c['purse'], (int) round($c['purse'] * $this->settings->int('entry_fee_pct') / 100), $runOn, $gameDate]
        );
    }

    private function insert(array $c, array $track, string $level, int $n, string $runOn, string $gameDate): void
    {
        [$lo, $hi] = $this->settings->intList("purse_$level");
        $c += ['track_id' => $track['id'], 'name' => "Race $n", 'race_level' => $level, 'purse' => (int) round(mt_rand($lo, $hi) / 500) * 500];
        if ($level === 'claiming') {
            $prices = $this->settings->intList('claiming_prices');
            $c['claiming_price'] = $prices[array_rand($prices)];
        }
        $this->insertRace($c, $runOn, $gameDate);
    }
}
