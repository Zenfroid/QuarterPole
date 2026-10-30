<?php
// The race calendar: pick a day, see its race cards.
use QuarterPole\View;

require __DIR__ . '/_boot.php';

$realToday = date('Y-m-d');
$day = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['day'] ?? '') ? $_GET['day'] : $realToday;
$days = $db->all(
    'SELECT run_on, MIN(game_date) AS game_date, COUNT(*) AS races FROM qp_races
      WHERE run_on BETWEEN DATE_SUB(?, INTERVAL 7 DAY) AND DATE_ADD(?, INTERVAL 14 DAY) GROUP BY run_on ORDER BY run_on',
    [$realToday, $realToday]
);
$races = $db->all(
    'SELECT r.*, t.name AS track_name,
            (SELECT COUNT(*) FROM qp_entries e WHERE e.race_id = r.id) AS field
       FROM qp_races r JOIN qp_tracks t ON t.id = r.track_id WHERE r.run_on = ? ORDER BY t.name, r.id',
    [$day]
);
View::render('races', $shared + compact('day', 'days', 'races', 'realToday'));
