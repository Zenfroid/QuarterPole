<?php
// Admin: the named stakes that run every game year.
use QuarterPole\Race\Stakes;
use QuarterPole\UserError;
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_admin();

$stakesSvc = new Stakes($db);
if ($isPost) {
    $sid = ($_POST['id'] ?? '') === '' ? null : (int) $_POST['id'];
    try {
        View::flash($stakesSvc->save($sid, $_POST));
    } catch (UserError $err) {
        // Keep what was typed so the form comes back filled in.
        $_SESSION['stakes_form'] = $_POST;
        View::flash($err->getMessage(), 'error');
        View::redirect('admin_stakes.php' . ($sid === null ? '?add=1' : '?edit=' . $sid));
    }
    View::redirect('admin_stakes.php');
}
$all = $stakesSvc->all();
$editId = (int) ($_GET['edit'] ?? 0);
$editing = null;
foreach ($all as $st) {
    if ((int) $st['id'] === $editId) {
        $editing = $st;
    }
}
if (isset($_SESSION['stakes_form'])) {
    $editing = array_merge($editing ?? ['series_name' => null, 'series_leg' => null], $_SESSION['stakes_form'],
        ['homebred_only' => !empty($_SESSION['stakes_form']['homebred_only']), 'active' => !empty($_SESSION['stakes_form']['active'])]);
    unset($_SESSION['stakes_form']);
}
$trackList = $db->all('SELECT * FROM qp_tracks ORDER BY active DESC, name');
View::render('admin_stakes', $shared + ['stakes' => $all, 'editing' => $editing, 'trackList' => $trackList, 'adding' => isset($_GET['add'])]);
