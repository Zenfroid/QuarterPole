<?php
declare(strict_types=1);

// Creates the game's own foundation stallions (8 to 10 years old, standing at stud).
// Run once from the command line:  php bin/create_game_stallions.php [count per surface]
// Their fee follows how good each one rolled.

use QuarterPole\GameClock;
use QuarterPole\HorseFactory;
use QuarterPole\Names;

if (PHP_SAPI !== 'cli') {
    exit('Run this from the command line.');
}
require dirname(__DIR__) . '/src/bootstrap.php';

$perSurface = max(1, (int) ($argv[1] ?? 6));
$factory = new HorseFactory($db, $settings, $clock);
$year = (int) $clock->today()->format('Y');

$made = 0;
foreach (['dirt', 'turf', 'steeplechase'] as $surface) {
    for ($i = 0; $i < $perSurface; $i++) {
        $name = Names::unique($db);

        $row = $factory->rollFoundation($surface);
        // Game studs are proven stock: lift their racing stats a little.
        foreach (['stat_break', 'stat_sprint', 'stat_speed', 'stat_drive', 'stat_potency'] as $stat) {
            $row[$stat] = min(100, $row[$stat] + mt_rand(0, 10));
        }
        $quality = array_sum(array_map(fn ($s) => $row[$s], HorseFactory::STATS)) / count(HorseFactory::STATS);
        // About 3,000 Cash for an average stud, rising steeply for the best.
        $fee = (int) max(1000, round((($quality - 40) ** 2) * 6, -2));

        $row += [
            'name'            => $name,
            'sex'             => 'M',
            'birth_game_date' => ($year - mt_rand(8, 10)) . '-01-01',
            'owner_id'        => null,
            'origin'          => 'game_stallion',
            'status'          => 'retired',
            'stud_fee'        => $fee,
            'book_size'       => $settings->int('book_cap_game'),
            'genetics_tested' => 1,
            'report_level'    => 'none',
        ];
        $cols = array_keys($row);
        $db->run(
            'INSERT INTO qp_horses (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')',
            array_values($row)
        );
        printf("%-20s %-12s fee %s\n", $name, $surface, number_format($fee));
        $made++;
    }
}
echo "Created $made game stallions.\n";
