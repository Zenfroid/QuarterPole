<?php
// One race: entries and the Enter form before it runs, the chart after.
use QuarterPole\Race\Entries;
use QuarterPole\Race\TrackConditions;
use QuarterPole\View;

require __DIR__ . '/_boot.php';

$race = $db->one(
    'SELECT r.*, t.name AS track_name, t.location, t.stretch, t.turn_style, t.turns
       FROM qp_races r JOIN qp_tracks t ON t.id = r.track_id WHERE r.id = ?',
    [(int) ($_GET['id'] ?? 0)]
);
if (!$race) {
    http_response_code(404);
    View::render('not_found', $shared);
    exit;
}
$field = $db->all(
    'SELECT e.*, h.name, h.origin, h.sex, h.birth_game_date, s.stable_name AS owner_name, j.name AS jockey_name,
            c.stable_name AS claimer_name,
            (SELECT COUNT(*) FROM qp_claims cl WHERE cl.entry_id = e.id AND cl.status = \'pending\') AS claims
       FROM qp_entries e JOIN qp_horses h ON h.id = e.horse_id JOIN qp_jockeys j ON j.id = e.jockey_id
       LEFT JOIN qp_stables s ON s.member_id = e.owner_id LEFT JOIN qp_stables c ON c.member_id = e.claimed_by
      WHERE e.race_id = ? ORDER BY e.finish_position IS NULL, e.finish_position, e.post_position, h.name',
    [$race['id']]
);
$calls = [];
foreach ($db->all('SELECT rc.* FROM qp_race_calls rc JOIN qp_entries e ON e.id = rc.entry_id WHERE e.race_id = ?', [$race['id']]) as $c) {
    $calls[$c['entry_id']][$c['call_point']] = $c;
}
$fractions = $db->all('SELECT * FROM qp_race_fractions WHERE race_id = ? ORDER BY at_f', [$race['id']]);
$entriesSvc = new Entries($db, $gameSettings);
$candidates = $stable && $race['status'] === 'open' ? $entriesSvc->candidates($member->id, $race) : [];
$fee = $stable ? $entriesSvc->fee($race, $stable) : (int) $race['entry_fee'];
$myClaims = $stable ? array_column($db->all(
    'SELECT cl.entry_id FROM qp_claims cl JOIN qp_entries e ON e.id = cl.entry_id WHERE e.race_id = ? AND cl.member_id = ?',
    [$race['id'], $member->id]
), 'entry_id') : [];
$isOpen = $race['status'] === 'open' && $race['run_on'] >= date('Y-m-d');
// The going: the race's own once it has run; otherwise the day's, once it's been set (today's is set on first look).
$conditions = new TrackConditions($db);
$going = $race['track_condition']
    ?? ($race['run_on'] === date('Y-m-d') ? $conditions->get((int) $race['track_id'], $race['run_on'], $race['surface'])
        : ($conditions->known($race['run_on'])[(int) $race['track_id']][$race['surface']] ?? null));
View::render('race', $shared + compact('race', 'field', 'calls', 'fractions', 'candidates', 'fee', 'myClaims', 'isOpen', 'going'));
