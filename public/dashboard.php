<?php
// The player's stable: their racing horses, badges, and the form to open a stable. Breeding horses are on breeding.php.
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_member();

if (!$stable) {
    if ($isPost) {
        $stables->create($member->id, (string) ($_POST['stable_name'] ?? ''));
        View::flash('Welcome to Quarter Pole! Your stable is open.');
        View::redirect('dashboard.php');
    }
    View::render('stable_setup', $shared);
    exit;
}

$p = max(1, (int) ($_GET['p'] ?? 1));
$list = $stables->horses($member->id, $p, 'active');
$barnCount = (int) $db->value("SELECT COUNT(*) FROM qp_horses WHERE owner_id = ? AND status = 'retired'", [$member->id]);
$badges = $db->all('SELECT b.badge_code, s.name FROM qp_badges b LEFT JOIN qp_series s ON s.badge_code = b.badge_code WHERE b.member_id = ? ORDER BY b.earned_at', [$member->id]);
View::render('dashboard', $shared + ['list' => $list, 'p' => $p, 'badges' => $badges, 'barnCount' => $barnCount]);
