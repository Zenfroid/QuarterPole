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
}
