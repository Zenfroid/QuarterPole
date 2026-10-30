<?php
// Handles entering, scratching and claiming on a race's page. ?do=enter, scratch or claim.
use QuarterPole\Race\Entries;
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_stable();
post_only();

$entriesSvc = new Entries($db, $gameSettings);
$raceId = (int) ($_POST['race_id'] ?? 0);
View::flash(match ($_GET['do'] ?? '') {
    'enter'   => $entriesSvc->enter($member->id, $raceId, (int) ($_POST['horse_id'] ?? 0), date('Y-m-d')),
    'scratch' => $entriesSvc->scratch($member->id, (int) ($_POST['entry_id'] ?? 0), date('Y-m-d')),
    'claim'   => $entriesSvc->claim($member->id, (int) ($_POST['entry_id'] ?? 0), date('Y-m-d')),
    default   => 'Nothing to do.',
});
View::redirect('race.php?id=' . $raceId);
