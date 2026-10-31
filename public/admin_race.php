<?php
// Admin: names an unnamed graded stakes from its race page.
use QuarterPole\Race\Stakes;
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_admin();
post_only();

$raceId = (int) ($_POST['race_id'] ?? 0);
View::flash((new Stakes($db))->nameRace($raceId, (string) ($_POST['name'] ?? ''), !empty($_POST['yearly'])));
View::redirect('race.php?id=' . $raceId);
