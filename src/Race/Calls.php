<?php
declare(strict_types=1);

namespace QuarterPole\Race;

// Points of call and fractional-time points by race distance, from the Brisnet chart.
// All distances are furlongs from the start.
final class Calls
{
    // distance => [1st call, 2nd call or null, [fractions before the finish]]
    private const TABLE = [
        '4.5'  => [2, null, [2, 4]],
        '5'    => [1.5, 3, [2, 4]],
        '5.5'  => [2, 3, [2, 4, 5]],
        '6'    => [2, 4, [2, 4, 5]],
        '6.5'  => [2, 4, [2, 4, 6]],
        '8'    => [4, 6, [2, 4, 6]],
        '9'    => [4, 6, [4, 6, 8]],
        '10'   => [4, 8, [4, 6, 8]],
        '12'   => [4, 10, [4, 6, 10]],
        '13'   => [4, 11, [4, 8, 10]],
        '14'   => [8, 12, [4, 10, 12]],
        '16'   => [8, 14, [4, 12, 14]],
    ];

    /** @return array{start:float, first:float, second:?float, stretch:float, fractions:float[]} */
    public static function forDistance(float $distance): array
    {
        if ($distance > 16) {
            // Long steeplechases: 1st call near halfway, 2nd about 2 furlongs before the stretch,
            // both rounded to the nearest quarter mile.
            $first  = round($distance / 2 / 2) * 2;
            $second = round(($distance - 3) / 2) * 2;
            $row = [$first, $second, [4, $first, $second]];
        } else {
            $row = self::TABLE['4.5'];
            foreach (self::TABLE as $d => $r) {
                if ($distance >= (float) $d) {
                    $row = $r;
                }
            }
        }
        $fractions = array_values(array_filter($row[2], fn ($f) => $f < $distance));
        $fractions[] = $distance;
        return [
            'start'     => 0.5,
            'first'     => (float) $row[0],
            'second'    => $row[1] === null ? null : (float) $row[1],
            'stretch'   => $distance - 1,
            'fractions' => array_map('floatval', $fractions),
        ];
    }

    /** "6 1/2 f", "1 1/16 mi" style label for a distance in furlongs. */
    public static function distanceLabel(float $f): string
    {
        if ($f < 8) {
            return self::fraction($f) . ' f';
        }
        return self::fraction($f / 8) . ' mi';
    }

    /** Lengths as a chart margin: nose, head, neck, 1/2, 3/4, 1 1/4 ... and "dist" (distanced) past 30. */
    public static function margin(float $lengths): string
    {
        if ($lengths >= 30) {
            return 'dist';
        }
        if ($lengths < 0.1) {
            return 'nose';
        }
        if ($lengths < 0.2) {
            return 'head';
        }
        if ($lengths < 0.35) {
            return 'neck';
        }
        return self::fraction(max(0.5, round($lengths * 4) / 4));
    }

    /** 2.25 => "2 1/4", 0.5 => "1/2". Quarters and sixteenths only. */
    public static function fraction(float $v): string
    {
        $whole = (int) floor($v + 1e-9);
        $rest = $v - $whole;
        $frac = '';
        foreach ([16, 8, 4, 2] as $den) {
            $num = (int) round($rest * $den);
            if (abs($num / $den - $rest) < 1e-6) {
                if ($num === 0 || $num === $den) {
                    $whole += $num === $den ? 1 : 0;
                    break;
                }
                $g = self::gcd($num, $den);
                $frac = ($num / $g) . '/' . ($den / $g);
            }
        }
        if ($whole === 0 && $frac !== '') {
            return $frac;
        }
        return trim($whole . ' ' . $frac);
    }

    private static function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : self::gcd($b, $a % $b);
    }
}
