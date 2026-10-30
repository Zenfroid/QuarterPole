<?php
// Admin: add, rename and retire jockeys.
use QuarterPole\Race\Jockeys;
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_admin();

$jockeysSvc = new Jockeys($db);
if ($isPost) {
    $n = $jockeysSvc->save((array) ($_POST['name'] ?? []), (array) ($_POST['active'] ?? []), (array) ($_POST['add'] ?? []));
    View::flash($n ? "Saved $n change" . ($n === 1 ? '' : 's') . '.' : 'Nothing changed.');
    View::redirect('admin_jockeys.php');
}
View::render('admin_jockeys', $shared + ['jockeys' => $jockeysSvc->all(), 'maxField' => $gameSettings->int('max_field_size')]);
