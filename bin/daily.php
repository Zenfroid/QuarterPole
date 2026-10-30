<?php
declare(strict_types=1);

// The daily cron. Run it once a day in the evening, after entries for the day's races close:
//   php bin/daily.php                 run today's races, recover Energy, move the game clock, add race cards
//   php bin/daily.php --schedule-only just add any missing race cards (safe to run any time)
// Running it twice on the same real day does nothing the second time.

use QuarterPole\Race\Runner;
use QuarterPole\Race\Schedule;

if (PHP_SAPI !== 'cli') {
    exit('Run this from the command line.');
}
require dirname(__DIR__) . '/src/bootstrap.php';

$today = date('Y-m-d');
$schedule = new Schedule($db, $settings, $clock);

if (in_array('--schedule-only', $argv, true)) {
    printf("Added %d races.\n", $schedule->fill($today));
    exit;
}

$last = $db->value('SELECT last_tick_at FROM qp_game_clock WHERE id = 1');
if ($last !== null && substr((string) $last, 0, 10) === $today) {
    echo "Already ran today ($last).\n";
    exit;
}

$runner = new Runner($db, $settings, $clock);
$done = $runner->runDue($today);
printf("Races run: %d, cancelled (no player horses): %d\n", $done['run'], $done['cancelled']);

$runner->recoverEnergy();
$db->run('UPDATE qp_game_clock SET game_date = DATE_ADD(game_date, INTERVAL ? DAY), last_tick_at = NOW() WHERE id = 1',
    [$settings->int('game_speed')]);
echo 'Game date is now ' . $clock->today()->format('Y-m-d') . ".\n";

$tomorrow = (new DateTimeImmutable($today))->modify('+1 day')->format('Y-m-d');
printf("Added %d races.\n", $schedule->fill($tomorrow));
