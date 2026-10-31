<?php
// One horse's page: record, pedigree, trainer's notes and the owner's options.
use QuarterPole\Horse;
use QuarterPole\Pedigree;
use QuarterPole\View;

require __DIR__ . '/_boot.php';

$horse = Horse::find($db, (int) ($_GET['id'] ?? 0));
if (!$horse || $horse['origin'] === 'ghost') {
    http_response_code(404);
    View::render('not_found', $shared);
    exit;
}
$isOwner = $member && (int) $horse['owner_id'] === $member->id;
$reports = $db->all(
    "SELECT level, notes FROM qp_trainer_reports WHERE horse_id = ? ORDER BY FIELD(level,'basic','experienced','expert')",
    [$horse['id']]
);
$tree = (new Pedigree($db))->tree($horse, 3);
$offspring = $db->all('SELECT id, name, birth_game_date FROM qp_horses WHERE sire_id = ? OR dam_id = ? ORDER BY birth_game_date DESC, name', [$horse['id'], $horse['id']]);
$testCost = $gameSettings->int('genetic_test_cost');
$reportCosts = ['experienced' => $gameSettings->int('report_cost_experienced'), 'expert' => $gameSettings->int('report_cost_expert')];
$trpCredits = $gameSettings->int('trp_credit_refund');
$raceRecord = $db->all(
    'SELECT e.finish_position, e.margin_text, e.earnings, r.id AS race_id, r.*, t.name AS track_name
       FROM qp_entries e JOIN qp_races r ON r.id = e.race_id JOIN qp_tracks t ON t.id = r.track_id
      WHERE e.horse_id = ? ORDER BY r.run_on DESC, r.id DESC LIMIT 30',
    [$horse['id']]
);
View::render('horse', $shared + compact('horse', 'isOwner', 'reports', 'tree', 'offspring', 'testCost', 'raceRecord', 'reportCosts', 'trpCredits'));
