<?php
// Book one of your mares to a stallion and name the foal.
use QuarterPole\Breeding;
use QuarterPole\Horse;
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_stable();

$breeding = new Breeding($db, $gameSettings, $clock);
if ($isPost) {
    $result = $breeding->breed($member->id, (int) $_POST['sire_id'], (int) $_POST['dam_id'], (int) $_POST['foal_year'], (string) $_POST['foal_name']);
    if ($result['born_dead']) {
        View::flash('Sadly, the foal was born dead. Both parents carry a lethal white gene.', 'error');
        View::redirect('horse.php?id=' . (int) $_POST['dam_id']);
    }
    View::flash('A foal is born!');
    View::redirect('horse.php?id=' . $result['foal_id']);
}
$sire = Horse::find($db, (int) ($_GET['sire'] ?? 0));
$mares = $breeding->mares($member->id);
$dam = null;
foreach ($mares as $m) {
    if ((int) $m['id'] === (int) ($_GET['dam'] ?? 0)) {
        $dam = $m;
    }
}
$proposal = null;
if ($sire && $dam) {
    $breeding->check($member->id, $sire, $dam);
    $proposal = $breeding->proposal($member->id, $sire, $dam);
}
View::render('breed', $shared + compact('sire', 'dam', 'mares', 'proposal'));
