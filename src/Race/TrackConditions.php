<?php
declare(strict_types=1);

namespace QuarterPole\Race;

use QuarterPole\Db;

// The going at each track: one condition per track, surface and race day, rolled the night
// before (or the first time anyone looks on race day) so players can see it before the races.
final class TrackConditions
{
    public function __construct(private Db $db) {}

    /**
     * Conditions for every track and surface racing on $runOn, rolling any that aren't set yet.
     * @return array<int, array<string, string>> track id => surface => condition
     */
    public function forDay(string $runOn): array
    {
        $needed = $this->db->all(
            'SELECT DISTINCT r.track_id, r.surface FROM qp_races r
               LEFT JOIN qp_track_conditions c ON c.track_id = r.track_id AND c.run_on = r.run_on AND c.surface = r.surface
              WHERE r.run_on = ? AND c.track_id IS NULL',
            [$runOn]
        );
        foreach ($needed as $n) {
            $this->get((int) $n['track_id'], $runOn, $n['surface']);
        }
        return $this->known($runOn);
    }

    /** Conditions already set for $runOn, without rolling new ones. */
    public function known(string $runOn): array
    {
        $out = [];
        foreach ($this->db->all('SELECT * FROM qp_track_conditions WHERE run_on = ?', [$runOn]) as $c) {
            $out[(int) $c['track_id']][$c['surface']] = $c['track_condition'];
        }
        return $out;
    }

    /** The condition for one track, day and surface, rolled and saved the first time it is asked for. */
    public function get(int $trackId, string $runOn, string $surface): string
    {
        $have = $this->db->value(
            'SELECT track_condition FROM qp_track_conditions WHERE track_id = ? AND run_on = ? AND surface = ?',
            [$trackId, $runOn, $surface]
        );
        if ($have !== null && $have !== false) {
            return (string) $have;
        }
        $track = $this->db->one('SELECT * FROM qp_tracks WHERE id = ?', [$trackId]);
        $condition = self::roll($track, $surface);
        // INSERT IGNORE: if two page views roll at once, the first one saved wins.
        $this->db->run(
            'INSERT IGNORE INTO qp_track_conditions (track_id, run_on, surface, track_condition) VALUES (?, ?, ?, ?)',
            [$trackId, $runOn, $surface, $condition]
        );
        return (string) $this->db->value(
            'SELECT track_condition FROM qp_track_conditions WHERE track_id = ? AND run_on = ? AND surface = ?',
            [$trackId, $runOn, $surface]
        );
    }

    /** "Good", "Sloppy" ... for display. */
    public static function label(string $condition): string
    {
        return ucfirst($condition);
    }

    /** True for anything but a fast dirt track or a firm turf course. */
    public static function isOff(string $condition): bool
    {
        return !in_array($condition, ['fast', 'firm'], true);
    }

    /** Track condition for the day, from the track's rain chance and profile. */
    private static function roll(array $track, string $surface): string
    {
        $rain = mt_rand(1, 100) <= (int) $track['rain_chance_pct'];
        $dryGood = ['mostly_fast' => 20, 'varied' => 40, 'wet' => 50][$track['condition_profile']] ?? 40;
        if ($surface === 'dirt') {
            return $rain ? (mt_rand(0, 1) ? 'muddy' : 'sloppy') : (mt_rand(1, 100) <= $dryGood ? 'good' : 'fast');
        }
        return $rain ? (mt_rand(1, 100) <= 60 ? 'yielding' : 'soft') : (mt_rand(1, 100) <= $dryGood ? 'good' : 'firm');
    }
}
