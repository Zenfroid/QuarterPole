<?php
// Admin: every tunable number in the game.
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_admin();

if ($isPost) {
    $changed = $gameSettings->save((array) ($_POST['s'] ?? []));
    View::flash($changed ? 'Saved: ' . implode(', ', $changed) . '.' : 'Nothing changed.');
    View::redirect('admin_settings.php');
}
View::render('admin_settings', $shared + ['groups' => $gameSettings->grouped()]);
