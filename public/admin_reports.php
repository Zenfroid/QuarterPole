<?php
// Admin: the lines trainers use in their reports.
use QuarterPole\TrainerNotes;
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_admin();

if ($isPost) {
    $n = TrainerNotes::saveLines($db, (array) ($_POST['line'] ?? []), (array) ($_POST['add'] ?? []));
    View::flash($n ? "Saved $n change" . ($n === 1 ? '' : 's') . '.' : 'Nothing changed.');
    View::redirect('admin_reports.php' . (isset($_POST['topic']) ? '?topic=' . rawurlencode((string) $_POST['topic']) : ''));
}
$topics = TrainerNotes::topics();
$topic = isset($topics[$_GET['topic'] ?? '']) ? $_GET['topic'] : array_key_first($topics);
$lines = $db->all('SELECT * FROM qp_report_lines WHERE topic = ? ORDER BY FIELD(band, \'exceptional\', \'very_good\', \'useful\', \'ordinary\', \'weak\'), id', [$topic]);
$counts = array_column($db->all('SELECT topic, COUNT(*) AS n FROM qp_report_lines GROUP BY topic'), 'n', 'topic');
View::render('admin_reports', $shared + compact('topics', 'topic', 'lines', 'counts'));
