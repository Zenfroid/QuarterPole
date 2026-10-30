<?php
declare(strict_types=1);

namespace QuarterPole\Race;

use QuarterPole\Db;
use QuarterPole\GameClock;
use QuarterPole\Settings;

// Builds race cards for the coming real days from the weekly schedule.
// Stakes days (Wednesday, Saturday, Sunday) wait for the race calendar.
final class Schedule
{
    // Real weekday (1 = Monday) => [track kind, race levels]
    private const WEEK = [
        1 => ['flat', ['claiming']],
        2 => ['flat', ['maiden', 'allowance']],
        4 => ['flat', ['maiden', 'allowance']],
        5 => ['steeplechase', ['maiden', 'claiming', 'allowance']],
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

    /** Surface, distance, age and sex conditions for one race. */
    private function design(array $track, string $level, string $kind): array
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
            [$min, $max] = $roll <= 50 ? [3, null] : [4, null];
        } elseif ($roll <= ($level === 'maiden' ? 35 : 20)) {
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

    private function insert(array $c, array $track, string $level, int $n, string $runOn, string $gameDate): void
    {
        [$lo, $hi] = $this->settings->intList("purse_$level");
        $purse = (int) round(mt_rand($lo, $hi) / 500) * 500;
        $claim = null;
        if ($level === 'claiming') {
            $prices = $this->settings->intList('claiming_prices');
            $claim = $prices[array_rand($prices)];
        }
        $this->db->run(
            'INSERT INTO qp_races (track_id, name, race_level, surface, distance_f, age_min, age_max, sex_rule,
                                   claiming_price, purse, entry_fee, run_on, game_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$track['id'], "Race $n", $level, $c['surface'], $c['distance_f'], $c['age_min'], $c['age_max'], $c['sex_rule'],
             $claim, $purse, (int) round($purse * $this->settings->int('entry_fee_pct') / 100), $runOn, $gameDate]
        );
    }
}
