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
            View::render('horse', $shared + compact('horse', 'isOwner', 'reports', 'tree', 'peakYears', 'offspring', 'testCost'));
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
                    'stud'    => $actions->setStud($member->id, $horseId, ($_POST['stud_fee'] ?? '') === '' ? null : (int) $_POST['stud_fee'], (int) ($_POST['book_size'] ?? 0)),
                    default   => 'Nothing to do.',
                });
                View::redirect('?page=horse&id=' . $horseId);
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
        'horse-action' => '?page=horse&id=' . (int) ($_POST['horse_id'] ?? 0),
        'breed' => '?page=breed&sire=' . (int) ($_POST['sire_id'] ?? $_GET['sire'] ?? 0) . '&dam=' . (int) ($_POST['dam_id'] ?? 0),
        default => '?page=' . rawurlencode($page),
    });
}
