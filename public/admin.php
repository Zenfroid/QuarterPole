<?php
// Admin panel home: links to every admin tool. Forum admins only.
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_admin();

$counts = [
    'horses'  => (int) $db->value("SELECT COUNT(*) FROM qp_horses WHERE origin <> 'ghost' AND status IN ('active','retired')"),
    'stables' => (int) $db->value('SELECT COUNT(*) FROM qp_stables'),
    'stakes'  => (int) $db->value('SELECT COUNT(*) FROM qp_stakes WHERE active = 1'),
    'jockeys' => (int) $db->value('SELECT COUNT(*) FROM qp_jockeys WHERE active = 1'),
];
View::render('admin', $shared + compact('counts'));
