<?php
// Handles the owner's buttons on a horse's page (racing surface, retire, test, trainers, pension, TRP, stud settings).
use QuarterPole\HorseActions;
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_stable();
post_only();

$horseId = (int) ($_POST['horse_id'] ?? 0);
$actions = new HorseActions($db, $gameSettings);
View::flash(match ($_POST['action'] ?? '') {
    'retire'  => $actions->retire($member->id, $horseId),
    'surface' => $actions->setSurface($member->id, $horseId, (string) ($_POST['surface'] ?? '')),
    'test'    => $actions->geneticTest($member->id, $horseId),
    'report_experienced' => $actions->upgradeReport($member->id, $horseId, 'experienced', $clock->today()),
    'report_expert'      => $actions->upgradeReport($member->id, $horseId, 'expert', $clock->today()),
    'pension' => $actions->pension($member->id, $horseId),
    'rehome'  => $actions->rehome($member->id, $horseId),
    'stud'    => $actions->setStud($member->id, $horseId, ($_POST['stud_fee'] ?? '') === '' ? null : (int) $_POST['stud_fee'], (int) ($_POST['book_size'] ?? 0)),
    default   => 'Nothing to do.',
});
View::redirect('horse.php?id=' . $horseId);
