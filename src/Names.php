<?php
declare(strict_types=1);

namespace QuarterPole;

// Made-up names for the game's own horses (stallions and ghosts), always unique.
final class Names
{
    private const FIRST = ['Royal', 'Silver', 'Northern', 'Iron', 'Golden', 'Midnight', 'Thunder', 'Brave', 'Storm', 'Copper',
        'Highland', 'Rapid', 'Noble', 'Crimson', 'Wild', 'Lucky', 'Steel', 'Valiant', 'Shadow', 'Prairie',
        'Autumn', 'Velvet', 'Distant', 'Quiet', 'Hidden', 'Painted', 'Scarlet', 'Winter', 'Rolling', 'Desert',
        'Southern', 'Morning', 'Evening', 'Blue', 'Gentle', 'Bold', 'Hasty', 'Restless', 'Cool', 'Sudden'];
    private const SECOND = ['Command', 'Legacy', 'Dancer', 'Express', 'Warrior', 'Ruler', 'Crown', 'Ransom', 'Ridge', 'Echo',
        'Charter', 'Banner', 'Harbor', 'Summit', 'Glory', 'Sovereign', 'Ledger', 'Comet', 'Anthem', 'Rogue',
        'Promise', 'Rumor', 'Journey', 'Lantern', 'Meadow', 'Spirit', 'Whisper', 'Gambit', 'Arrow', 'Harvest',
        'Thistle', 'Venture', 'Rebel', 'Sparrow', 'Gallop', 'Tempest', 'Riddle', 'Pilgrim', 'Monarch', 'Saga'];

    public static function unique(Db $db): string
    {
        for ($try = 0; $try < 50; $try++) {
            $name = self::FIRST[array_rand(self::FIRST)] . ' ' . self::SECOND[array_rand(self::SECOND)];
            if ($try >= 20) {
                $name = mb_substr($name, 0, 15) . ' ' . ['II', 'III', 'IV', 'V'][mt_rand(0, 3)];
            }
            if (!$db->value('SELECT 1 FROM qp_horses WHERE name = ?', [$name])) {
                return $name;
            }
        }
        return 'Runner ' . mt_rand(10000, 99999);
    }
}
