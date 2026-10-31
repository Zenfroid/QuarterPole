<?php
declare(strict_types=1);

namespace QuarterPole;

use QuarterPole\Genetics\Color;

// Works out a foal's hidden stats from its parents' peak stats.
final class Foal
{
    public function __construct(private Settings $settings) {}

    /** Share of the foal that comes from the sire, by Potency. */
    public static function sireShare(array $sire, array $dam): float
    {
        $ps = (int) $sire['stat_potency'];
        $pd = (int) $dam['stat_potency'];
        return $ps / max(1, $ps + $pd);
    }

    /** Dud chance (percent) for a given COI. */
    public function dudChance(float $coi): int
    {
        return match (true) {
            $coi >= 0.25   => $this->settings->int('dud_pct_severe'),
            $coi >= 0.125  => $this->settings->int('dud_pct_half_sibling'),
            $coi >= 0.0625 => $this->settings->int('dud_pct_first_cousin'),
            default        => $this->settings->int('dud_pct'),
        };
    }

    /** Stats, style, surface, peak age, greatness and dud for a foal. Color genes are added by the caller. */
    public function roll(array $sire, array $dam, float $coi): array
    {
        $share = self::sireShare($sire, $dam);
        $fromSire = fn () => mt_rand() / mt_getrandmax() < $share;
        $s = $this->settings;

        $row = [];
        foreach (HorseFactory::STATS as $stat) {
            $blend = $share * (int) $sire[$stat] + (1 - $share) * (int) $dam[$stat];
            $row[$stat] = (int) round($blend) + mt_rand($s->int('swing_min'), $s->int('swing_max'));
        }

        $endSwing = $s->float('swing_endurance_f');
        $staSwing = $s->float('swing_stamina_f');
        $endurance = $share * (float) $sire['endurance_f'] + (1 - $share) * (float) $dam['endurance_f']
            + mt_rand((int) (-$endSwing * 2), (int) ($endSwing * 2)) / 2;
        $stamina = $share * (float) $sire['stamina_f'] + (1 - $share) * (float) $dam['stamina_f']
            + mt_rand((int) (-$staSwing * 4), (int) ($staSwing * 4)) / 4;
        $row['endurance_f'] = round(max(4.0, min(40.0, round($endurance * 2) / 2)), 2);
        $row['stamina_f']   = round(max(0.25, min(3.0, round($stamina * 4) / 4)), 2);

        // Surface: inherited by Potency share, with a chance of something different.
        $surface = $fromSire() ? $sire['surface_pref'] : $dam['surface_pref'];
        if (mt_rand(1, 100) <= $s->int('surface_mutation_pct')) {
            $others = array_values(array_diff(['dirt', 'turf', 'steeplechase'], [$surface]));
            $surface = $others[mt_rand(0, 1)];
        }
        $row['surface_pref']  = $surface;
        $row['running_style'] = ['front', 'steady', 'close'][mt_rand(0, 2)];
        $row['peak_age']      = max(2, min(5, (int) ($fromSire() ? $sire['peak_age'] : $dam['peak_age']) + mt_rand(-1, 1)));
        $range = $s->intList('peak_years');
        [$shortest, $longest] = [min($range), max($range)];
        $row['peak_years']    = max($shortest, min($longest, (int) ($fromSire() ? $sire['peak_years'] : $dam['peak_years']) + mt_rand(-1, 1)));

        $row['is_dud']   = (int) (mt_rand(1, 100) <= $this->dudChance($coi));
        $row['is_great'] = (int) (!$row['is_dud'] && mt_rand(1, 1000) <= $s->float('greatness_pct') * 10);

        foreach (HorseFactory::STATS as $stat) {
            if ($row['is_great'] && !in_array($stat, ['stat_temper', 'stat_potency'], true)) {
                $row[$stat] = max($s->int('greatness_floor') + mt_rand(0, 5), $row[$stat] + mt_rand(0, $s->int('greatness_boost_max')));
            }
            if ($row['is_dud'] && $stat !== 'stat_potency') {
                $row[$stat] -= mt_rand($s->int('dud_drop_min'), $s->int('dud_drop_max'));
            }
            $row[$stat] = max(25, min(100, $row[$stat]));
        }
        $row['coi'] = round($coi, 4);
        return $row;
    }

    public static function colorFields(array $genes): array
    {
        return $genes + ['color_name' => Color::name($genes)];
    }
}
