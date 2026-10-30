<?php
declare(strict_types=1);

// Front controller: every page is index.php?page=...

use QuarterPole\Auth\DevAuth;
use QuarterPole\Breeding;
use QuarterPole\Csrf;
use QuarterPole\Horse;
use QuarterPole\HorseActions;
use QuarterPole\HorseFactory;
use QuarterPole\Pedigree;
use QuarterPole\Race\Entries;
use QuarterPole\Stable;
use QuarterPole\Training;
use QuarterPole\UserError;
use QuarterPole\View;

require dirname(__DIR__) . '/src/bootstrap.php';

$page   = $_GET['page'] ?? 'home';
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($isPost) {
    Csrf::check();
}

$member  = $auth->member();
$stables = new Stable($db, $settings);
$stable  = $member ? $stables->find($member->id) : null;
$shared  = ['config' => $config, 'member' => $member, 'stable' => $stable, 'auth' => $auth, 'today' => $clock->today()];

try {
    switch ($page) {
        case 'dev-login':
            if (!$auth instanceof DevAuth) {
                View::redirect('?');
            }
            if ($isPost) {
                $auth->login(max(1, (int) $_POST['id']), trim($_POST['name']) ?: 'Tester', !empty($_POST['admin']));
                View::redirect('?');
            }
            View::render('dev_login', $shared);
            break;

        case 'dev-logout':
            if ($auth instanceof DevAuth) {
                $auth->logout();
            }
            View::redirect('?');

        case 'horse':
            $horse = Horse::find($db, (int) ($_GET['id'] ?? 0));
            if (!$horse || $horse['origin'] === 'ghost') {
                http_response_code(404);
                View::render('not_found', $shared);
                break;
            }
            $isOwner = $member && (int) $horse['owner_id'] === $member->id;
            $reports = $db->all(
                "SELECT level, notes FROM qp_trainer_reports WHERE horse_id = ? ORDER BY FIELD(level,'basic','experienced','expert')",
                [$horse['id']]
            );
            $tree = (new Pedigree($db))->tree($horse, 3);
            $peakYears = $settings->int('peak_years');
            $offspring = $db->all('SELECT id, name, birth_game_date FROM qp_horses WHERE sire_id = ? OR dam_id = ? ORDER BY birth_game_date DESC, name', [$horse['id'], $horse['id']]);
            $testCost = $settings->int('genetic_test_cost');
            $reportCosts = ['experienced' => $settings->int('report_cost_experienced'), 'expert' => $settings->int('report_cost_expert')];
            $trpCredits = $settings->int('trp_credit_refund');
            $raceRecord = $db->all(
                'SELECT e.finish_position, e.margin_text, e.earnings, r.id AS race_id, r.*, t.name AS track_name
                   FROM qp_entries e JOIN qp_races r ON r.id = e.race_id JOIN qp_tracks t ON t.id = r.track_id
                  WHERE e.horse_id = ? ORDER BY r.run_on DESC, r.id DESC LIMIT 30',
                [$horse['id']]
            );
            View::render('horse', $shared + compact('horse', 'isOwner', 'reports', 'tree', 'peakYears', 'offspring', 'testCost', 'raceRecord', 'reportCosts', 'trpCredits'));
            break;

        case 'races':
            $realToday = date('Y-m-d');
            $day = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['day'] ?? '') ? $_GET['day'] : $realToday;
            $days = $db->all(
                'SELECT run_on, MIN(game_date) AS game_date, COUNT(*) AS races FROM qp_races
                  WHERE run_on BETWEEN DATE_SUB(?, INTERVAL 7 DAY) AND DATE_ADD(?, INTERVAL 14 DAY) GROUP BY run_on ORDER BY run_on',
                [$realToday, $realToday]
            );
            $races = $db->all(
                'SELECT r.*, t.name AS track_name,
                        (SELECT COUNT(*) FROM qp_entries e WHERE e.race_id = r.id) AS field
                   FROM qp_races r JOIN qp_tracks t ON t.id = r.track_id WHERE r.run_on = ? ORDER BY t.name, r.id',
                [$day]
            );
            View::render('races', $shared + compact('day', 'days', 'races', 'realToday'));
            break;

        case 'race':
            $race = $db->one(
                'SELECT r.*, t.name AS track_name, t.location, t.stretch, t.turn_style, t.turns
                   FROM qp_races r JOIN qp_tracks t ON t.id = r.track_id WHERE r.id = ?',
                [(int) ($_GET['id'] ?? 0)]
            );
            if (!$race) {
                http_response_code(404);
                View::render('not_found', $shared);
                break;
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
            $entriesSvc = new Entries($db, $settings);
            $candidates = $stable && $race['status'] === 'open' ? $entriesSvc->candidates($member->id, $race) : [];
            $fee = $stable ? $entriesSvc->fee($race, $stable) : (int) $race['entry_fee'];
            $myClaims = $stable ? array_column($db->all(
                'SELECT cl.entry_id FROM qp_claims cl JOIN qp_entries e ON e.id = cl.entry_id WHERE e.race_id = ? AND cl.member_id = ?',
                [$race['id'], $member->id]
            ), 'entry_id') : [];
            $isOpen = $race['status'] === 'open' && $race['run_on'] >= date('Y-m-d');
            View::render('race', $shared + compact('race', 'field', 'calls', 'fractions', 'candidates', 'fee', 'myClaims', 'isOpen'));
            break;

        default:
            // Everything below needs a logged-in member with a stable.
            if (!$member) {
                View::render('welcome', $shared);
                break;
            }
            if (!$stable) {
                if ($isPost && $page === 'create-stable') {
                    $stables->create($member->id, (string) $_POST['stable_name']);
                    View::flash('Welcome to Quarter Pole! Your stable is open.');
                    View::redirect('?');
                }
                View::render('stable_setup', $shared);
                break;
            }

            if ($page === 'train' && $isPost) {
                $horseId = (int) ($_POST['horse_id'] ?? 0);
                View::flash((new Training($db, $settings))->train($member->id, $horseId, date('Y-m-d')));
                View::redirect(($_POST['back'] ?? '') === 'stable' ? '?p=' . max(1, (int) ($_POST['p'] ?? 1)) : '?page=horse&id=' . $horseId);
            }

            if ($page === 'horse-action' && $isPost) {
                $horseId = (int) ($_POST['horse_id'] ?? 0);
                $actions = new HorseActions($db, $settings);
                View::flash(match ($_POST['action'] ?? '') {
                    'retire'  => $actions->retire($member->id, $horseId),
                    'test'    => $actions->geneticTest($member->id, $horseId),
                    'report_experienced' => $actions->upgradeReport($member->id, $horseId, 'experienced', $clock->today()),
                    'report_expert'      => $actions->upgradeReport($member->id, $horseId, 'expert', $clock->today()),
                    'pension' => $actions->pension($member->id, $horseId),
                    'rehome'  => $actions->rehome($member->id, $horseId),
                    'stud'    => $actions->setStud($member->id, $horseId, ($_POST['stud_fee'] ?? '') === '' ? null : (int) $_POST['stud_fee'], (int) ($_POST['book_size'] ?? 0)),
                    default   => 'Nothing to do.',
                });
                View::redirect('?page=horse&id=' . $horseId);
            }

            if (in_array($page, ['race-enter', 'race-scratch', 'race-claim'], true) && $isPost) {
                $entriesSvc = new Entries($db, $settings);
                $raceId = (int) ($_POST['race_id'] ?? 0);
                View::flash(match ($page) {
                    'race-enter'   => $entriesSvc->enter($member->id, $raceId, (int) $_POST['horse_id'], date('Y-m-d')),
                    'race-scratch' => $entriesSvc->scratch($member->id, (int) $_POST['entry_id'], date('Y-m-d')),
                    'race-claim'   => $entriesSvc->claim($member->id, (int) $_POST['entry_id'], date('Y-m-d')),
                });
                View::redirect('?page=race&id=' . $raceId);
            }

            if ($page === 'admin-settings') {
                if (!$member->isAdmin) {
                    http_response_code(403);
                    View::render('not_found', $shared);
                    break;
                }
                if ($isPost) {
                    $changed = $settings->save((array) ($_POST['s'] ?? []));
                    View::flash($changed ? 'Saved: ' . implode(', ', $changed) . '.' : 'Nothing changed.');
                    View::redirect('?page=admin-settings');
                }
                View::render('admin_settings', $shared + ['groups' => $settings->grouped()]);
                break;
            }

            if ($page === 'studs') {
                $surface = in_array($_GET['surface'] ?? '', ['dirt', 'turf', 'steeplechase'], true) ? $_GET['surface'] : null;
                $studs = (new Breeding($db, $settings, $clock))->studs($surface);
                $bookCaps = ['game' => $settings->int('book_cap_game'), 'player' => $settings->int('book_cap_player')];
                View::render('studs', $shared + compact('studs', 'surface', 'bookCaps'));
                break;
            }

            if ($page === 'breed') {
                $breeding = new Breeding($db, $settings, $clock);
                if ($isPost) {
                    $result = $breeding->breed($member->id, (int) $_POST['sire_id'], (int) $_POST['dam_id'], (int) $_POST['foal_year'], (string) $_POST['foal_name']);
                    if ($result['born_dead']) {
                        View::flash('Sadly, the foal was born dead. Both parents carry a lethal white gene.', 'error');
                        View::redirect('?page=horse&id=' . (int) $_POST['dam_id']);
                    }
                    View::flash('A foal is born!');
                    View::redirect('?page=horse&id=' . $result['foal_id']);
                }
                $sire = Horse::find($db, (int) ($_GET['sire'] ?? 0));
                $mares = $breeding->mares($member->id);
                $dam = null;
                foreach ($mares as $m) {
                    if ((int) $m['id'] === (int) ($_GET['dam'] ?? 0)) {
                        $dam = $m;
                    }
                }
                $proposal = null;
                if ($sire && $dam) {
                    $breeding->check($member->id, $sire, $dam);
                    $proposal = $breeding->proposal($member->id, $sire, $dam);
                }
                View::render('breed', $shared + compact('sire', 'dam', 'mares', 'proposal'));
                break;
            }

            if ($page === 'create-horse') {
                if ($isPost) {
                    $id = (new HorseFactory($db, $settings, $clock))->createFoundation($member->id, [
                        'name'    => (string) $_POST['name'],
                        'sex'     => (string) $_POST['sex'],
                        'surface' => (string) $_POST['surface'],
                        'age'     => (int) $_POST['age'],
                    ]);
                    View::flash('Your new foundation horse has arrived.');
                    View::redirect('?page=horse&id=' . $id);
                }
                View::render('create_horse', $shared);
                break;
            }

            $p = max(1, (int) ($_GET['p'] ?? 1));
            $list = $stables->horses($member->id, $p);
            View::render('stable', $shared + ['list' => $list, 'p' => $p]);
    }
} catch (UserError $err) {
    // Show the problem and return the player to the form they came from.
    View::flash($err->getMessage(), 'error');
    View::redirect(match ($page) {
        'create-stable', 'train' => '?',
        'race-enter', 'race-scratch', 'race-claim' => '?page=race&id=' . (int) ($_POST['race_id'] ?? 0),
        'horse-action' => '?page=horse&id=' . (int) ($_POST['horse_id'] ?? 0),
        'breed' => '?page=breed&sire=' . (int) ($_POST['sire_id'] ?? $_GET['sire'] ?? 0) . '&dam=' . (int) ($_POST['dam_id'] ?? 0),
        default => '?page=' . rawurlencode($page),
    });
}
