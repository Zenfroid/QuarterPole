<?php
declare(strict_types=1);

namespace QuarterPole;

// Writes the static trainer's report. Players never see numbers, only these notes,
// and they describe the horse at its peak. Lower-level trainers misjudge more.
// The wording lives in qp_report_lines so it can be edited on the admin page.
final class TrainerNotes
{
    // How far off (in stat points) each trainer can be, and how many stats they comment on.
    private const LEVELS = [
        'basic'       => ['error' => 12, 'stats' => 4],
        'experienced' => ['error' => 6,  'stats' => 7],
        'expert'      => ['error' => 2,  'stats' => 10],
    ];

    public const STATS = ['break', 'sprint', 'speed', 'dexterity', 'temper', 'drive', 'track', 'scope', 'recovery', 'potency'];
    public const BANDS = ['exceptional' => 85, 'very_good' => 70, 'useful' => 55, 'ordinary' => 40, 'weak' => 0];

    /** @var array<string,string[]>|null "topic|band" => lines */
    private static ?array $lines = null;

    /** Every topic with a short explanation, in the order a report uses them (for the admin page). */
    public static function topics(): array
    {
        $topics = [
            'style_front' => 'Running style: front runner', 'style_steady' => 'Running style: steady', 'style_close' => 'Running style: closer',
            'surface_dirt' => 'Prefers dirt', 'surface_turf' => 'Prefers turf', 'surface_steeplechase' => 'Prefers steeplechase',
            'distance_sprint' => 'Endurance under 7 f', 'distance_mile' => 'Endurance 7 to 9 f', 'distance_classic' => 'Endurance 9 to 11 f',
            'distance_route' => 'Endurance 11 to 16 f', 'distance_marathon' => 'Endurance 16 f and over',
        ];
        foreach (self::STATS as $s) {
            $topics["stat_$s"] = ucfirst($s);
        }
        return $topics;
    }

    public static function write(Db $db, array $h, string $level): string
    {
        $cfg = self::LEVELS[$level];
        $stats = self::STATS;
        shuffle($stats);
        $stats = array_slice($stats, 0, $cfg['stats']);

        $out = [];
        $out[] = self::pick($db, 'style_' . $h['running_style']);
        $out[] = self::pick($db, 'surface_' . $h['surface_pref']);
        $out[] = self::pick($db, self::distanceTopic((float) $h['endurance_f'] + self::noise($cfg['error'] / 6)));
        foreach ($stats as $stat) {
            $judged = (int) $h["stat_$stat"] + (int) round(self::noise($cfg['error']));
            $out[] = self::pick($db, "stat_$stat", self::band($judged));
        }
        $out = array_filter($out, fn ($l) => $l !== null);
        return str_replace('{name}', (string) ($h['name'] ?? 'This horse'), implode("\n", $out));
    }

    /**
     * Saves the admin page: edited lines (an emptied line is removed) and new ones.
     * @param array<int,string> $edits id => text
     * @param array<string,string> $added "topic|band" => text
     */
    public static function saveLines(Db $db, array $edits, array $added): int
    {
        $topics = self::topics();
        $changes = 0;
        $db->transaction(function (Db $db) use ($edits, $added, $topics, &$changes) {
            foreach ($edits as $id => $text) {
                $text = trim(preg_replace('/\s+/', ' ', (string) $text));
                if ($text === '') {
                    $changes += $db->run('DELETE FROM qp_report_lines WHERE id = ?', [(int) $id])->rowCount();
                } else {
                    $changes += $db->run('UPDATE qp_report_lines SET line = ? WHERE id = ?', [mb_substr($text, 0, 255), (int) $id])->rowCount();
                }
            }
            foreach ($added as $slot => $text) {
                $text = trim(preg_replace('/\s+/', ' ', (string) $text));
                [$topic, $band] = array_pad(explode('|', (string) $slot, 2), 2, '');
                $isStat = str_starts_with($topic, 'stat_');
                if ($text === '' || !isset($topics[$topic]) || ($isStat && !isset(self::BANDS[$band]))) {
                    continue;
                }
                $db->run('INSERT INTO qp_report_lines (topic, band, line) VALUES (?, ?, ?)', [$topic, $isStat ? $band : null, mb_substr($text, 0, 255)]);
                $changes++;
            }
        });
        self::$lines = null;
        return $changes;
    }

    private static function pick(Db $db, string $topic, ?string $band = null): ?string
    {
        if (self::$lines === null) {
            self::$lines = [];
            foreach ($db->all('SELECT topic, band, line FROM qp_report_lines') as $r) {
                self::$lines[$r['topic'] . '|' . $r['band']][] = $r['line'];
            }
        }
        $choices = self::$lines["$topic|$band"] ?? [];
        return $choices ? $choices[array_rand($choices)] : null;
    }

    private static function band(int $v): string
    {
        foreach (self::BANDS as $band => $min) {
            if ($v >= $min) {
                return $band;
            }
        }
        return 'weak';
    }

    private static function distanceTopic(float $f): string
    {
        return match (true) {
            $f < 7  => 'distance_sprint',
            $f < 9  => 'distance_mile',
            $f < 11 => 'distance_classic',
            $f < 16 => 'distance_route',
            default => 'distance_marathon',
        };
    }

    /** Roughly bell-shaped error between -$max and +$max. */
    private static function noise(float $max): float
    {
        $u = (mt_rand() / mt_getrandmax() + mt_rand() / mt_getrandmax()) - 1;  // -1..1, peaks at 0
        return $u * $max;
    }
}
