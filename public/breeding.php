<?php
// The breeding barn: the stable's retired stallions and mares. Racing horses are on dashboard.php.
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_stable();

$p = max(1, (int) ($_GET['p'] ?? 1));
$list = $stables->horses($member->id, $p, 'retired');
$racingCount = (int) $db->value("SELECT COUNT(*) FROM qp_horses WHERE owner_id = ? AND status = 'active'", [$member->id]);
View::render('breeding', $shared + ['list' => $list, 'p' => $p, 'racingCount' => $racingCount]);
