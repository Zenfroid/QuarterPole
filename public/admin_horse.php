<?php
// Admin: edit one horse.
use QuarterPole\AdminHorses;
use QuarterPole\Horse;
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_admin();

$hid = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
if ($isPost) {
    $changed = (new AdminHorses($db))->save($hid, $_POST);
    View::flash($changed ? 'Saved: ' . implode(', ', $changed) . '.' : 'Nothing changed.');
    View::redirect('admin_horse.php?id=' . $hid);
}
$horse = Horse::find($db, $hid);
if (!$horse) {
    http_response_code(404);
    View::render('not_found', $shared);
    exit;
}
View::render('admin_horse', $shared + compact('horse'));
