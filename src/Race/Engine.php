<?php
declare(strict_types=1);

namespace QuarterPole\Race;

// The race simulation. Each horse's speed is worked out every 1/16 mile from its stats
// and situation; the field then moves forward in small time steps, so positions,
// margins and fractional times can be read off at any point.
//
// Runners come in with CURRENT stats (peak x maturity), so this class never touches the database.
final class Engine
{
    public const FT_PER_FURLONG = 660.0;
    public const FT_PER_LENGTH  = 8.0;
    private const DT = 0.05;   // seconds per time step

    /** Speed in ft/s at stat 25 and the extra at stat 100. */
    private const CRUISE = [50.0, 9.0];
    private const KICK   = [53.0, 10.0];

    // Share of Stamina spent on the break (the rest is the stretch run).
    private const EARLY_SHARE = ['front' => 0.7, 'steady' => 0.3, 'close' => 0.1];

    private const TURN_LOSS = ['tight' => 0.045, 'normal' => 0.03, 'wide' => 0.015];
    private const CONDITION = [
        // condition => [severity 0-3 for the Track stat, overall slowdown]
        'fast' => [0, 0.0], 'good' => [1, 0.005], 'muddy' => [2, 0.015], 'sloppy' => [3, 0.01],
        'firm' => [0, 0.0], 'yielding' => [2, 0.02], 'soft' => [3, 0.04],
    ];
    private const SURFACE_PACE = ['dirt' => 1.0, 'turf' => 1.0, 'steeplechase' => 0.9];

    /** @param array<string,float> $tune overrides for the tunable numbers below */
    public function __construct(private array $tune = [])
    {
        $this->tune += [
            'noise'          => 0.02,   // per-segment random speed swing (1 sd)
            'form'           => 0.02,   // per-race random form (1 sd)
            'off_surface'    => 0.05,   // penalty on a non-preferred surface
            'track_penalty'  => 0.015,  // per severity step, at Track 25
            'stretch_effect' => 0.03,   // long stretch helps the late kick, short helps the early one
        ];
    }

    /**
     * @param array $race   distance_f, surface, track_condition
     * @param array $track  stretch, turns, turn_style, jumps
     * @param array<int|string,array> $runners keyed by entry id: stat_* (current), running_style,
     *        endurance_f, stamina_f, surface_pref, energy, jockey_skill
     * @return array{order:array, finish:array, calls:array, fractions:array}
     */
    public function run(array $race, array $track, array $runners): array
    {
        $distF   = (float) $race['distance_f'];
        $distFt  = $distF * self::FT_PER_FURLONG;
        $segFt   = self::FT_PER_FURLONG / 2;
        $calls   = Calls::forDistance($distF);
        $turns   = $this->turnMap($track, $distF);
        $jumps   = $this->jumpPoints($race, $track, $distFt);
        [$sev, $slow] = self::CONDITION[$race['track_condition']] ?? [0, 0.0];
        $stretchK = ['short' => -1, 'normal' => 0, 'long' => 1][$track['stretch']] ?? 0;

        $h = [];
        foreach ($runners as $key => $r) {
            $n = fn (string $s) => max(0.0, min(1.0, ((float) $r["stat_$s"] - 25) / 75));
            $stamina = (float) $r['stamina_f'] * self::FT_PER_FURLONG;
            $early   = $stamina * self::EARLY_SHARE[$r['running_style']];
            $base = (1 - $slow) * self::SURFACE_PACE[$race['surface']]
                * (0.85 + 0.15 * (float) $r['energy'] / 100)
                * (1 + ((float) ($r['jockey_skill'] ?? 50) - 50) * 0.0004)
                * (1 + $this->gauss() * $this->tune['form'])
                * ($r['surface_pref'] === $race['surface'] ? 1 : 1 - $this->tune['off_surface'])
                * (1 - $this->tune['track_penalty'] * $sev * (1 - $n('track')));
            $breakN = $n('break');
            $delay = 0.2 + 0.8 * (1 - $breakN) + mt_rand(0, 300) / 1000;
            if (mt_rand(1, 1000) <= 30 * (1 - $breakN)) {
                $delay += 0.8;   // stumbled at the start
            }
            $h[$key] = [
                'n' => ['speed' => $n('speed'), 'sprint' => $n('sprint'), 'dex' => $n('dexterity'),
                        'temper' => $n('temper'), 'drive' => $n('drive'), 'scope' => $n('scope')],
                'base' => $base, 'pauseUntil' => $delay, 'seg' => -1, 'v' => 0.0, 'd' => 0.0,
                'earlyEnd' => $early, 'lateStart' => $distFt - ($stamina - $early),
                'endurance' => (float) $r['endurance_f'] * self::FT_PER_FURLONG,
                'fade' => (0.5 + 1.5 * $n('drive')) * self::FT_PER_FURLONG,
                'nextJump' => 0, 'time' => null,
            ];
        }

        $callPoints = ['start' => $calls['start'] * self::FT_PER_FURLONG, 'first' => $calls['first'] * self::FT_PER_FURLONG,
                       'stretch' => $calls['stretch'] * self::FT_PER_FURLONG];
        if ($calls['second'] !== null) {
            $callPoints['second'] = $calls['second'] * self::FT_PER_FURLONG;
        }
        asort($callPoints);
        $callSnap = [];
        $fracTimes = [];
        $fracLeft = $calls['fractions'];

        $t = 0.0;
        $running = count($h);
        while ($running > 0 && $t < 3600) {
            $t += self::DT;
            $positions = array_column($h, 'd');
            foreach ($h as $key => &$x) {
                if ($x['time'] !== null || $t < $x['pauseUntil']) {
                    continue;
                }
                $seg = (int) floor($x['d'] / $segFt);
                if ($seg !== $x['seg']) {
                    $x['seg'] = $seg;
                    $x['v'] = $this->segmentSpeed($x, $positions, $turns[$seg] ?? null, $distFt, $stretchK, $seg === 0);
                }
                $prev = $x['d'];
                $x['d'] += $x['v'] * self::DT;

                if (isset($jumps[$x['nextJump']]) && $x['d'] >= $jumps[$x['nextJump']]) {
                    $x['nextJump']++;
                    $loss = 0.3 + 1.2 * (1 - $x['n']['scope']) + mt_rand(0, 300) / 1000;
                    if (mt_rand(1, 1000) <= 20 + 60 * (1 - $x['n']['scope'])) {
                        $loss += 1.5;   // a mistake at the fence
                    }
                    $x['pauseUntil'] = $t + $loss;
                }
                if ($x['d'] >= $distFt) {
                    // Exact crossing time inside this step.
                    $x['time'] = $t - self::DT + self::DT * ($distFt - $prev) / max(1e-6, $x['d'] - $prev);
                    $running--;
                }
                // Leader's fractional times.
                while ($fracLeft && $x['d'] >= $fracLeft[0] * self::FT_PER_FURLONG) {
                    $at = array_shift($fracLeft);
                    $fracTimes[(string) $at] = $x['time'] ?? ($t - self::DT + self::DT * ($at * self::FT_PER_FURLONG - $prev) / max(1e-6, $x['d'] - $prev));
                }
            }
            unset($x);

            // Points of call: snapshot the field when the leader reaches each one.
            $lead = max(array_column($h, 'd'));
            foreach ($callPoints as $name => $ft) {
                if (!isset($callSnap[$name]) && $lead >= $ft) {
                    $callSnap[$name] = $this->snapshot($h, min($lead, $distFt));
                }
            }
        }

        // Finish order and margins (lengths behind the winner, at the loser's own speed).
        $times = array_column($h, 'time', null);
        $keys = array_keys($h);
        $finishTimes = array_combine($keys, $times);
        asort($finishTimes);
        $order = array_keys($finishTimes);
        $winTime = reset($finishTimes);
        $finish = [];
        foreach ($order as $i => $key) {
            $lengths = ($finishTimes[$key] - $winTime) * $h[$key]['v'] / self::FT_PER_LENGTH;
            $finish[$key] = ['position' => $i + 1, 'time' => round($finishTimes[$key], 2), 'lengths' => round($lengths, 2)];
        }
        $callSnap['finish'] = [];
        foreach ($finish as $key => $f) {
            $callSnap['finish'][$key] = ['position' => $f['position'], 'lengths' => $f['lengths']];
        }

        return ['order' => $order, 'finish' => $finish, 'calls' => $callSnap, 'fractions' => $fracTimes, 'call_at' => $calls];
    }

    private function segmentSpeed(array $x, array $positions, ?string $turn, float $distFt, int $stretchK, bool $first): float
    {
        $n = $x['n'];
        $d = $x['d'];
        if ($d < $x['earlyEnd']) {
            $v = (self::KICK[0] + self::KICK[1] * $n['sprint']) * (1 - $this->tune['stretch_effect'] * $stretchK * 0.7);
        } elseif ($d >= $x['lateStart']) {
            $v = (self::KICK[0] + self::KICK[1] * $n['sprint']) * (1 + $this->tune['stretch_effect'] * $stretchK);
        } else {
            $v = self::CRUISE[0] + self::CRUISE[1] * $n['speed'];
        }
        if ($first) {
            $v *= 0.8;   // standing start
        }

        // Past its Endurance the horse fades toward half speed; Drive slows the fade.
        if ($d > $x['endurance']) {
            $v *= max(0.5, 1 - 0.5 * ($d - $x['endurance']) / $x['fade']);
        }
        if ($turn !== null) {
            $v *= 1 - self::TURN_LOSS[$turn] * (1 - 0.8 * $n['dex']);
        }

        // Traffic: horses just ahead can box this one in; Dexterity finds the gap.
        $ahead = 0;
        $close = 0;
        $rivalNear = false;
        foreach ($positions as $p) {
            $gap = $p - $d;
            if ($gap > 0 && $gap < 2 * self::FT_PER_LENGTH) {
                $ahead++;
            }
            if ($gap !== 0.0 && abs($gap) < self::FT_PER_LENGTH * 0.75) {
                $close++;
            }
            if ($gap !== 0.0 && abs($gap) < 2 * self::FT_PER_LENGTH) {
                $rivalNear = true;
            }
        }
        if ($ahead >= 2 && mt_rand(1, 1000) <= 150 * ($ahead - 1) * (1 - 0.8 * $n['dex'])) {
            $v *= 0.96;
        }
        // Jostling: low Temper loses ground in a tight pack.
        if ($close >= 2 && mt_rand(1, 100) <= 20) {
            $v *= 1 - 0.03 * (1 - $n['temper']);
        }
        // Drive: a battle in the last quarter mile.
        if ($rivalNear && $d >= $distFt - 2 * self::FT_PER_FURLONG) {
            $v *= 1 + 0.02 * $n['drive'];
        }

        return $v * $x['base'] * (1 + $this->gauss() * $this->tune['noise']);
    }

    /**
     * Which 1/16-mile segments are on a turn. Laid out backward from the finish:
     * the home stretch, a turn, a straight, a turn... repeating every lap.
     * Two-turn tracks are one-mile ovals; three-turn tracks are bigger triangles.
     * @return array<int,string> segment => turn style
     */
    private function turnMap(array $track, float $distF): array
    {
        $turnLen = ['tight' => 1.5, 'normal' => 2.0, 'wide' => 2.5];
        $stretch = ['short' => 1.5, 'normal' => 2.0, 'long' => 2.75][$track['stretch']] ?? 2.0;
        $turns = max(1, (int) $track['turns']);
        $styles = [];
        for ($i = 0; $i < $turns; $i++) {
            $styles[] = $track['turn_style'] === 'mixed' ? ($i % 2 ? 'wide' : 'tight') : $track['turn_style'];
        }
        $lap = 8.0 + ($turns - 2) * 3;
        $other = $turns > 1 ? max(1.0, ($lap - $stretch - array_sum(array_map(fn ($s) => $turnLen[$s], $styles))) / ($turns - 1)) : 2.0;

        // Pieces from the finish backward: [turn style or null, length].
        $pieces = [];
        $covered = 0.0;
        while ($covered < $distF) {
            foreach ($styles as $i => $style) {
                $pieces[] = [null, $i === 0 ? $stretch : $other];
                $pieces[] = [$style, $turnLen[$style]];
                $covered += ($i === 0 ? $stretch : $other) + $turnLen[$style];
            }
        }
        $map = [];
        $segs = (int) round($distF * 2);
        for ($seg = 0; $seg < $segs; $seg++) {
            $mid = $distF - ($seg + 0.5) / 2;   // furlongs from the finish
            $at = 0.0;
            foreach ($pieces as [$style, $len]) {
                if ($mid < $at + $len) {
                    if ($style !== null) {
                        $map[$seg] = $style;
                    }
                    break;
                }
                $at += $len;
            }
        }
        return $map;
    }

    /** Jump points in feet, evenly spaced, none in the last furlong. */
    private function jumpPoints(array $race, array $track, float $distFt): array
    {
        if ($race['surface'] !== 'steeplechase' || (int) $track['jumps'] === 0) {
            return [];
        }
        $count = max(1, (int) round((float) $race['distance_f'] * (int) $track['jumps'] / 24));
        $usable = $distFt - self::FT_PER_FURLONG;
        $points = [];
        for ($i = 1; $i <= $count; $i++) {
            $points[] = $usable * $i / ($count + 0.5);
        }
        return $points;
    }

    private function snapshot(array $h, float $leadFt): array
    {
        $pos = array_map(fn ($x) => min($x['d'], $leadFt), $h);
        arsort($pos);
        $out = [];
        $i = 0;
        foreach ($pos as $key => $ft) {
            $out[$key] = ['position' => ++$i, 'lengths' => round(($leadFt - $ft) / self::FT_PER_LENGTH, 2)];
        }
        return $out;
    }

    private function gauss(): float
    {
        $u = max(1e-9, mt_rand() / mt_getrandmax());
        $v = mt_rand() / mt_getrandmax();
        return sqrt(-2 * log($u)) * cos(2 * M_PI * $v);
    }
}
