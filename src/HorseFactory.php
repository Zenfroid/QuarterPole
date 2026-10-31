<?php
declare(strict_types=1);

namespace QuarterPole;

use QuarterPole\Genetics\Color;

// Creates foundation horses (paid with one creation credit).
final class HorseFactory
{
    public const STATS = ['stat_break', 'stat_sprint', 'stat_speed', 'stat_dexterity', 'stat_temper',
        'stat_drive', 'stat_track', 'stat_scope', 'stat_recovery', 'stat_potency'];

    // Foundation Endurance range in furlongs by surface.
    private const ENDURANCE = [
        'dirt'         => [5.0, 12.0],
        'turf'         => [5.0, 16.0],
        'steeplechase' => [16.0, 34.0],
    ];

    public function __construct(private Db $db, private Settings $settings, private GameClock $clock) {}

    /**
     * @param array{name:string, sex:string, surface:string, age:int} $input
     * @return int the new horse id
     */
    public function createFoundation(int $memberId, array $input): int
    {
        $name    = Horse::validateName($this->db, $input['name']);
        $sex     = $input['sex'] === 'F' ? 'F' : 'M';
        $surface = $input['surface'];
        $age     = (int) $input['age'];
        if (!isset(self::ENDURANCE[$surface])) {
            throw new UserError('Pick a surface.');
        }
        if ($age < 2 || $age > 20) {
            throw new UserError('Foundation horses must be 2 to 20 years old.');
        }

        $row = $this->rollFoundation($surface);
        $row += [
            'name'            => $name,
            'sex'             => $sex,
            'birth_game_date' => ($this->clock->today()->format('Y') - $age) . '-01-01',
            'owner_id'        => $memberId,
            'origin'          => 'foundation',
            'report_level'    => 'basic',
        ];

        return $this->db->transaction(function (Db $db) use ($memberId, $row) {
            $credits = (int) $db->value('SELECT creation_credits FROM qp_stables WHERE member_id = ? FOR UPDATE', [$memberId]);
            if ($credits < 1) {
                throw new UserError('You have no creation credits left.');
            }
            $db->run('UPDATE qp_stables SET creation_credits = creation_credits - 1 WHERE member_id = ?', [$memberId]);

            $cols = array_keys($row);
            $db->run(
                'INSERT INTO qp_horses (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')',
                array_values($row)
            );
            $id = $db->lastId();
            // Foundation horses are at least 2, so the free basic report is written now.
            $db->run(
                "INSERT INTO qp_trainer_reports (horse_id, level, notes) VALUES (?, 'basic', ?)",
                [$id, TrainerNotes::write($db, $row, 'basic')]
            );
            return $id;
        });
    }

    /** Random stats, style, peak age and color for a foundation horse. */
    public function rollFoundation(string $surface): array
    {
        $style = ['front', 'steady', 'close'][mt_rand(0, 2)];
        $lean  = $this->settings->int('running_style_lean');

        $row = ['surface_pref' => $surface, 'running_style' => $style];
        foreach (self::STATS as $stat) {
            // Average of two rolls clusters foundation stock around the low 60s.
            $row[$stat] = intdiv(mt_rand(35, 85) + mt_rand(35, 85), 2);
        }

        [$lo, $hi] = self::ENDURANCE[$surface];
        if ($surface === 'dirt' && mt_rand(1, 100) <= 7) {
            $hi = 14.0;  // the occasional dirt horse that stays further
        }
        $endurance = self::furlongs($lo, $hi);
        $stamina   = self::furlongs(0.5, 2.0);

        // Running style leans: front runners break, closers kick, steady runners stay.
        switch ($style) {
            case 'front':
                $row['stat_break'] += mt_rand(0, $lean);
                $stamina -= 0.25;
                break;
            case 'close':
                $row['stat_break'] -= mt_rand(0, $lean);
                $stamina += 0.25;
                break;
            case 'steady':
                $endurance += 0.5;
                break;
        }
        foreach (self::STATS as $stat) {
            $row[$stat] = max(25, min(100, $row[$stat]));
        }
        $row['endurance_f'] = round(min($hi + 0.5, max($lo, $endurance)), 2);
        $row['stamina_f']   = round(max(0.5, min(2.0, $stamina)), 2);
        $row['peak_age']    = mt_rand(2, 5);
        $range = $this->settings->intList('peak_years');
        [$shortest, $longest] = [min($range), max($range)];
        $row['peak_years']  = mt_rand($shortest, $longest);

        $genes = Color::randomFoundation();
        $row  += $genes;
        $row['color_name'] = Color::name($genes);
        return $row;
    }

    /** Random furlongs between $lo and $hi, rounded to the nearest 1/16 mile. */
    private static function furlongs(float $lo, float $hi): float
    {
        $steps = (int) round(($hi - $lo) * 2);
        return $lo + mt_rand(0, $steps) / 2;
    }
}
