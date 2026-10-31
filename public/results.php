<?php
// Past results: every race that has run, newest first, 25 to a page. Filter by track or surface.
use QuarterPole\View;

require __DIR__ . '/_boot.php';

$perPage = 25;
$p = max(1, (int) ($_GET['p'] ?? 1));
$tracks = $db->all('SELECT id, name FROM qp_tracks WHERE active = 1 ORDER BY name');
$track = (int) ($_GET['track'] ?? 0);
$surface = in_array($_GET['surface'] ?? '', ['dirt', 'turf', 'steeplechase'], true) ? $_GET['surface'] : '';

$where = "r.status = 'run'";
$args = [];
if ($track) {
    $where .= ' AND r.track_id = ?';
    $args[] = $track;
}
if ($surface !== '') {
    $where .= ' AND r.surface = ?';
    $args[] = $surface;
}
$total = (int) $db->value("SELECT COUNT(*) FROM qp_races r WHERE $where", $args);
$pages = max(1, (int) ceil($total / $perPage));
$p = min($p, $pages);
$offset = ($p - 1) * $perPage;
$results = $db->all(
    "SELECT r.*, t.name AS track_name, h.id AS winner_id, h.name AS winner_name, h.origin AS winner_origin, s.stable_name AS winner_owner,
            (SELECT COUNT(*) FROM qp_entries x WHERE x.race_id = r.id) AS field
       FROM qp_races r JOIN qp_tracks t ON t.id = r.track_id
       LEFT JOIN qp_entries w ON w.race_id = r.id AND w.finish_position = 1
       LEFT JOIN qp_horses h ON h.id = w.horse_id
       LEFT JOIN qp_stables s ON s.member_id = w.owner_id
      WHERE $where ORDER BY r.run_on DESC, t.name, r.id LIMIT $perPage OFFSET $offset",
    $args
);
View::render('results', $shared + compact('results', 'tracks', 'track', 'surface', 'p', 'pages', 'total'));
