<?php
declare(strict_types=1);

// The daily cron. Run it once a day in the evening, after entries for the day's races close:
//   php bin/daily.php                 run today's races, recover Energy, move the game clock,
//                                     write notes for new 2-year-olds, add race cards
//   php bin/daily.php --schedule-only just add any missing race cards (safe to run any time)
// Running it twice on the same real day does nothing the second time.

use QuarterPole\Db;
use QuarterPole\Race\Runner;
use QuarterPole\Race\Schedule;
use QuarterPole\TrainerNotes;

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

// Foals that have just turned 2 get their free basic trainer's notes.
$turning2 = $db->all(
    "SELECT * FROM qp_horses WHERE report_level = 'none' AND origin IN ('bred','backdated','foundation')
        AND status IN ('active','retired') AND YEAR(birth_game_date) <= ?",
    [(int) $clock->today()->format('Y') - 2]
);
foreach ($turning2 as $h) {
    $db->transaction(function (Db $db) use ($h) {
        $db->run("INSERT INTO qp_trainer_reports (horse_id, level, notes) VALUES (?, 'basic', ?)", [$h['id'], TrainerNotes::write($db, $h, 'basic')]);
        $db->run("UPDATE qp_horses SET report_level = 'basic' WHERE id = ?", [$h['id']]);
    });
}
printf("Basic trainer's notes written: %d\n", count($turning2));

$tomorrow = (new DateTimeImmutable($today))->modify('+1 day')->format('Y-m-d');
printf("Added %d races.\n", $schedule->fill($tomorrow));
