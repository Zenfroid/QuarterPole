<?php
declare(strict_types=1);

namespace QuarterPole;

// Reads tunable numbers from qp_settings. Loaded once per request.
final class Settings
{
    private ?array $values = null;

    public function __construct(private Db $db) {}

    public function get(string $key, ?string $default = null): string
    {
        if ($this->values === null) {
            $this->values = [];
            foreach ($this->db->all('SELECT setting_key, setting_value FROM qp_settings') as $row) {
                $this->values[$row['setting_key']] = $row['setting_value'];
            }
        }
        if (!array_key_exists($key, $this->values)) {
            if ($default === null) {
                throw new \RuntimeException("Missing setting: $key");
            }
            return $default;
        }
        return $this->values[$key];
    }

    public function int(string $key): int
    {
        return (int) $this->get($key);
    }

    public function float(string $key): float
    {
        return (float) $this->get($key);
    }

    /** For comma lists such as purse_split "60,20,11,6,3". */
    public function intList(string $key): array
    {
        return array_map('intval', explode(',', $this->get($key)));
    }

    /** Every setting with its description, grouped for the admin page. */
    public function grouped(): array
    {
        $groups = [];
        foreach ($this->db->all('SELECT * FROM qp_settings ORDER BY setting_key') as $row) {
            $groups[self::group($row['setting_key'])][] = $row;
        }
        $order = ['Site', 'Economy', 'AMI and credits', 'Breeding', 'Aging and training', 'Racing', 'Other'];
        return array_replace(array_intersect_key(array_flip($order), $groups), $groups);
    }

    /**
     * Saves changed values. A number stays a number and a comma list stays a list of numbers.
     * @param array<string,string> $input key => new value
     * @return string[] keys that changed
     */
    public function save(array $input): array
    {
        $changed = [];
        $current = array_column($this->db->all('SELECT setting_key, setting_value FROM qp_settings'), 'setting_value', 'setting_key');
        foreach ($input as $key => $value) {
            if (!array_key_exists($key, $current)) {
                continue;
            }
            $value = trim((string) $value);
            if (str_contains($current[$key], ',')) {
                $value = str_replace(' ', '', $value);
            }
            if ($value === $current[$key]) {
                continue;
            }
            $num = '-?\d+(\.\d+)?';
            if (preg_match("/^$num$/", $current[$key]) && !preg_match("/^$num$/", $value)) {
                throw new UserError("$key must be a number.");
            }
            if (preg_match("/^$num(,$num)+$/", $current[$key]) && !preg_match("/^$num(,$num)*$/", $value)) {
                throw new UserError("$key must be a list of numbers separated by commas.");
            }
            if (mb_strlen($value) > 255) {
                throw new UserError("$key is too long.");
            }
            $changed[$key] = $value;
        }
        $this->db->transaction(function (Db $db) use ($changed) {
            foreach ($changed as $key => $value) {
                $db->run('UPDATE qp_settings SET setting_value = ? WHERE setting_key = ?', [$value, $key]);
            }
        });
        $this->values = null;
        return array_keys($changed);
    }

    private static function group(string $key): string
    {
        $prefixes = [
            'Site'               => ['site_', 'forum_', 'game_speed'],
            'AMI and credits'    => ['ami_', 'starting_credits', 'grand_opening', 'trp_'],
            'Breeding'           => ['swing_', 'greatness_', 'dud_', 'surface_mutation', 'book_', 'mare_', 'stallion_', 'running_style', 'genetic_'],
            'Aging and training' => ['peak_', 'old_age', 'training_'],
            'Racing'             => ['race_', 'max_field', 'min_field', 'energy_', 'recovery_', 'schedule_', 'races_per', 'purse_', 'claiming_', 'ghost_', 'entry_fee'],
            'Economy'            => ['starting_cash', 'report_cost', 'bank_', 'premium_', 'auction_'],
        ];
        foreach ($prefixes as $group => $list) {
            foreach ($list as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    return $group;
                }
            }
        }
        return 'Other';
    }
}
