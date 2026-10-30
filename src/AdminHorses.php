<?php
declare(strict_types=1);

namespace QuarterPole;

// Admin panel: find any horse and edit it directly.
final class AdminHorses
{
    // Whole-number fields: column => [label, min, max]
    public const NUMBERS = [
        'stat_break'      => ['Break', 1, 100],
        'stat_sprint'     => ['Sprint', 1, 100],
        'stat_speed'      => ['Speed', 1, 100],
        'stat_dexterity'  => ['Dexterity', 1, 100],
        'stat_temper'     => ['Temper', 1, 100],
        'stat_drive'      => ['Drive', 1, 100],
        'stat_track'      => ['Track', 1, 100],
        'stat_scope'      => ['Scope', 1, 100],
        'stat_recovery'   => ['Recovery', 1, 100],
        'stat_potency'    => ['Potency', 1, 100],
        'energy'          => ['Energy %', 0, 100],
        'peak_age'        => ['Peak age', 2, 5],
        'starts'          => ['Starts', 0, 65000],
        'wins'            => ['Wins', 0, 65000],
        'places'          => ['Seconds', 0, 65000],
        'shows'           => ['Thirds', 0, 65000],
        'earnings'        => ['Earnings', 0, 2_000_000_000],
    ];

    // Decimal fields: column => [label, min, max]
    public const DECIMALS = [
        'endurance_f'       => ['Endurance (furlongs)', 1, 40],
        'stamina_f'         => ['Stamina (furlongs of kick)', 0.25, 3],
        'training_progress' => ['Training progress (0 to 100)', 0, 100],
    ];

    public const CHOICES = [
        'sex'           => ['M' => 'Male', 'F' => 'Female'],
        'status'        => ['active' => 'Active', 'retired' => 'Retired to breeding', 'pensioned' => 'Pensioned', 'rehomed' => 'Rehomed (TRP)', 'deceased' => 'Deceased'],
        'surface_pref'  => ['dirt' => 'Dirt', 'turf' => 'Turf', 'steeplechase' => 'Steeplechase'],
        'running_style' => ['front' => 'Front runner', 'steady' => 'Steady', 'close' => 'Closer'],
    ];

    public function __construct(private Db $db) {}

    /** Horses whose name contains $q, or the horse with that id. Ghosts are left out unless asked for by id. */
    public function search(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return $this->db->all(
                "SELECT h.*, s.stable_name FROM qp_horses h LEFT JOIN qp_stables s ON s.member_id = h.owner_id
                  WHERE h.origin <> 'ghost' ORDER BY h.id DESC LIMIT 50"
            );
        }
        return $this->db->all(
            "SELECT h.*, s.stable_name FROM qp_horses h LEFT JOIN qp_stables s ON s.member_id = h.owner_id
              WHERE h.id = ? OR (h.origin <> 'ghost' AND (h.name LIKE ? OR s.stable_name LIKE ?)) ORDER BY h.name LIMIT 100",
            [ctype_digit($q) ? (int) $q : 0, '%' . $q . '%', '%' . $q . '%']
        );
    }

    /** Saves the admin form. Returns the names of the fields that changed. */
    public function save(int $id, array $in): array
    {
        $h = $this->db->one('SELECT * FROM qp_horses WHERE id = ?', [$id]);
        if (!$h) {
            throw new UserError('That horse does not exist.');
        }
        $new = [];

        $name = trim(preg_replace('/\s+/', ' ', (string) ($in['name'] ?? '')));
        if ($name !== $h['name']) {
            $new['name'] = Horse::validateName($this->db, $name);
        }

        $owner = trim((string) ($in['owner_id'] ?? ''));
        $owner = $owner === '' ? null : (int) $owner;
        if ($owner !== null && !$this->db->value('SELECT 1 FROM qp_stables WHERE member_id = ?', [$owner])) {
            throw new UserError("No stable belongs to member $owner.");
        }
        if ($owner !== ($h['owner_id'] === null ? null : (int) $h['owner_id'])) {
            $new['owner_id'] = $owner;
        }

        foreach (self::CHOICES as $col => $options) {
            $v = (string) ($in[$col] ?? $h[$col]);
            if (!isset($options[$v])) {
                throw new UserError('Pick a value for ' . str_replace('_', ' ', $col) . '.');
            }
            if ($v !== $h[$col]) {
                $new[$col] = $v;
            }
        }
        foreach (['gelded', 'offered_for_sale', 'genetics_tested'] as $col) {
            $v = empty($in[$col]) ? 0 : 1;
            if ($v !== (int) $h[$col]) {
                $new[$col] = $v;
            }
        }
        if (($new['sex'] ?? $h['sex']) === 'F' && ($new['gelded'] ?? (int) $h['gelded'])) {
            throw new UserError('Only males can be gelded.');
        }

        foreach (self::NUMBERS as $col => [$label, $min, $max]) {
            $raw = trim((string) ($in[$col] ?? $h[$col]));
            if (!preg_match('/^\d+$/', $raw) || (int) $raw < $min || (int) $raw > $max) {
                throw new UserError("$label must be a whole number from $min to " . number_format($max) . '.');
            }
            if ((int) $raw !== (int) $h[$col]) {
                $new[$col] = (int) $raw;
            }
        }
        foreach (self::DECIMALS as $col => [$label, $min, $max]) {
            $raw = trim((string) ($in[$col] ?? $h[$col]));
            if (!is_numeric($raw) || (float) $raw < $min || (float) $raw > $max) {
                throw new UserError("$label must be from $min to $max.");
            }
            if (round((float) $raw, 2) !== round((float) $h[$col], 2)) {
                $new[$col] = round((float) $raw, 2);
            }
        }

        $fee = trim((string) ($in['stud_fee'] ?? ''));
        $fee = $fee === '' ? null : $fee;
        if ($fee !== null && (!preg_match('/^\d+$/', $fee) || (int) $fee > 10_000_000)) {
            throw new UserError('Stud fee must be blank or a whole number up to 10,000,000.');
        }
        $fee = $fee === null ? null : (int) $fee;
        if ($fee !== ($h['stud_fee'] === null ? null : (int) $h['stud_fee'])) {
            $new['stud_fee'] = $fee;
        }

        if ($new) {
            $sets = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($new)));
            $this->db->run("UPDATE qp_horses SET $sets WHERE id = ?", [...array_values($new), $id]);
        }
        return array_keys($new);
    }
}
