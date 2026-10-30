<?php
declare(strict_types=1);

namespace QuarterPole;

// Writes the static trainer's report. Players never see numbers, only these notes,
// and they describe the horse at its peak. Lower-level trainers misjudge more.
final class TrainerNotes
{
    // How far off (in stat points) each trainer can be, and how many stats they comment on.
    private const LEVELS = [
        'basic'       => ['error' => 12, 'stats' => 4],
        'experienced' => ['error' => 6,  'stats' => 7],
        'expert'      => ['error' => 2,  'stats' => 10],
    ];

    private const PHRASES = [
        'stat_break'     => 'out of the gate',
        'stat_sprint'    => 'when asked to kick',
        'stat_speed'     => 'at cruising pace',
        'stat_dexterity' => 'through the turns and in traffic',
        'stat_temper'    => 'in temperament',
        'stat_drive'     => 'in will to win',
        'stat_track'     => 'on an off track',
        'stat_scope'     => 'over fences',
        'stat_recovery'  => 'at bouncing back after a race',
        'stat_potency'   => 'as a prospect for breeding',
    ];

    public static function write(array $h, string $level): string
    {
        $cfg = self::LEVELS[$level];
        $stats = array_keys(self::PHRASES);
        shuffle($stats);
        $stats = array_slice($stats, 0, $cfg['stats']);

        $lines = [];
        $lines[] = self::styleLine($h['running_style']) . ' Prefers ' . self::surfaceWord($h['surface_pref']) . '.';
        $lines[] = self::distanceLine((float) $h['endurance_f'] + self::noise($cfg['error'] / 6));

        foreach ($stats as $stat) {
            $judged = (int) $h[$stat] + (int) round(self::noise($cfg['error']));
            $lines[] = ucfirst(self::band($judged)) . ' ' . self::PHRASES[$stat] . '.';
        }
        return implode("\n", $lines);
    }

    private static function band(int $v): string
    {
        return match (true) {
            $v >= 85 => 'exceptional',
            $v >= 70 => 'very good',
            $v >= 55 => 'useful',
            $v >= 40 => 'ordinary',
            default  => 'weak',
        };
    }

    private static function styleLine(string $style): string
    {
        return match ($style) {
            'front'  => 'Likes to go to the front and set the pace.',
            'close'  => 'Likes to sit back and make one big run late.',
            default  => 'Settles mid-pack and runs an even race.',
        };
    }

    private static function surfaceWord(string $s): string
    {
        return match ($s) {
            'turf'         => 'the turf',
            'steeplechase' => 'jumping',
            default        => 'the dirt',
        };
    }

    private static function distanceLine(float $f): string
    {
        return match (true) {
            $f < 7    => 'Looks like a sprinter.',
            $f < 9    => 'Should handle a mile or so.',
            $f < 11   => 'Should get a mile and a quarter.',
            $f < 16   => 'Will stay a long way.',
            default   => 'Built to stay all day over jumps.',
        };
    }

    /** Roughly bell-shaped error between -$max and +$max. */
    private static function noise(float $max): float
    {
        $u = (mt_rand() / mt_getrandmax() + mt_rand() / mt_getrandmax()) - 1;  // -1..1, peaks at 0
        return $u * $max;
    }
}
