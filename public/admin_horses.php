<?php
// Admin: search for horses to edit.
use QuarterPole\AdminHorses;
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_admin();

$q = (string) ($_GET['q'] ?? '');
$found = (new AdminHorses($db))->search($q);
View::render('admin_horses', $shared + compact('q', 'found'));
