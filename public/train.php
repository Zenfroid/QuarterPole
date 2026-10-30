<?php
// Handles the Train button on the dashboard and on a horse's page.
use QuarterPole\Training;
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_stable();
post_only();

$horseId = (int) ($_POST['horse_id'] ?? 0);
View::flash((new Training($db, $gameSettings))->train($member->id, $horseId, date('Y-m-d')));
View::redirect(($_POST['back'] ?? '') === 'stable' ? 'dashboard.php?p=' . max(1, (int) ($_POST['p'] ?? 1)) : 'horse.php?id=' . $horseId);
