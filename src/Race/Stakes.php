<?php
declare(strict_types=1);

namespace QuarterPole\Race;

use QuarterPole\Db;
use QuarterPole\UserError;

// The named stakes that recur every game year (admin page).
final class Stakes
{
    public const WEEKDAYS = ['wed' => 'Wednesday', 'sat' => 'Saturday', 'sun' => 'Sunday (steeplechase)'];

    public function __construct(private Db $db) {}

    public function all(): array
    {
        return $this->db->all(
            'SELECT s.*, t.name AS track_name, t.active AS track_active, se.name AS series_name
               FROM qp_stakes s JOIN qp_tracks t ON t.id = s.track_id LEFT JOIN qp_series se ON se.id = s.series_id
              ORDER BY s.game_month, s.game_day, s.weekday, s.name'
        );
    }

    /** Adds ($id null) or updates a stakes race. Open races already on the cards take the new name. */
    public function save(?int $id, array $in): string
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) ($in['name'] ?? '')));
        if ($name === '' || mb_strlen($name) > 80) {
            throw new UserError('Give the race a name of up to 80 characters.');
        }
        $track = $this->db->one('SELECT * FROM qp_tracks WHERE id = ?', [(int) ($in['track_id'] ?? 0)]);
        if (!$track) {
            throw new UserError('Pick a track.');
        }
        $surface = (string) ($in['surface'] ?? '');
        if (!in_array($surface, ['dirt', 'turf', 'steeplechase'], true) || !$track["has_$surface"]) {
            throw new UserError($track['name'] . " doesn't have a $surface course.");
        }
        $weekday = (string) ($in['weekday'] ?? '');
        if (!isset(self::WEEKDAYS[$weekday]) || ($weekday === 'sun') !== ($surface === 'steeplechase')) {
            throw new UserError('Steeplechases run on Sundays; flat stakes on Wednesdays or Saturdays.');
        }
        $level = (string) ($in['race_level'] ?? '');
        if (!in_array($level, ['g1', 'g2', 'g3'], true)) {
            throw new UserError('Pick a grade.');
        }
        $distance = round((float) ($in['distance_f'] ?? 0) * 2) / 2;
        if ($distance < 4.5 || $distance > 40) {
            throw new UserError('Distance must be 4 1/2 to 40 furlongs.');
        }
        $ageMin = (int) ($in['age_min'] ?? 0);
        $ageMax = ($in['age_max'] ?? '') === '' ? null : (int) $in['age_max'];
        if ($ageMin < 2 || $ageMin > 10 || ($ageMax !== null && $ageMax < $ageMin)) {
            throw new UserError('Check the ages: youngest 2 or more, oldest blank for "and up".');
        }
        if ($surface === 'steeplechase' && $ageMin < 4) {
            throw new UserError('Steeplechases are for 4-year-olds and up.');
        }
        $sex = (string) ($in['sex_rule'] ?? 'open');
        if (!in_array($sex, ['open', 'colts_geldings', 'fillies_mares'], true)) {
            $sex = 'open';
        }
        $month = (int) ($in['game_month'] ?? 0);
        $day = (int) ($in['game_day'] ?? 0);
        if ($month < 1 || $month > 12 || $day < 1 || $day > 28) {
            throw new UserError('Pick a game month and a day from 1 to 28.');
        }
        $purse = (int) ($in['purse'] ?? 0);
        if ($purse < 1000 || $purse > 10_000_000) {
            throw new UserError('Purse must be 1,000 to 10,000,000 Cash.');
        }
        $row = [
            'name' => $name, 'track_id' => (int) $track['id'], 'race_level' => $level, 'surface' => $surface, 'distance_f' => $distance,
            'age_min' => $ageMin, 'age_max' => $ageMax, 'sex_rule' => $sex, 'homebred_only' => (int) !empty($in['homebred_only']),
            'game_month' => $month, 'game_day' => $day, 'weekday' => $weekday, 'purse' => $purse, 'active' => (int) !empty($in['active']),
        ];
        $cols = array_keys($row);
        if ($id === null) {
            $this->db->run(
                'INSERT INTO qp_stakes (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')',
                array_values($row)
            );
            return "$name is on the calendar.";
        }
        $this->db->transaction(function (Db $db) use ($id, $row, $cols) {
            $db->run('UPDATE qp_stakes SET ' . implode(', ', array_map(fn ($c) => "$c = ?", $cols)) . ' WHERE id = ?', [...array_values($row), $id]);
            $db->run("UPDATE qp_races SET name = ? WHERE stakes_id = ? AND status = 'open'", [$row['name'], $id]);
        });
        return "$name is saved. Changes apply from the next time it is put on the cards; only the name changes on cards already out.";
    }
}
