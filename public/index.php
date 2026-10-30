<?php
declare(strict_types=1);

// Front controller: every page is index.php?page=...

use QuarterPole\Auth\DevAuth;
use QuarterPole\Csrf;
use QuarterPole\Horse;
use QuarterPole\HorseFactory;
use QuarterPole\Stable;
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
            $parents = [
                'sire' => $horse['sire_id'] ? Horse::find($db, (int) $horse['sire_id']) : null,
                'dam'  => $horse['dam_id'] ? Horse::find($db, (int) $horse['dam_id']) : null,
            ];
            View::render('horse', $shared + compact('horse', 'isOwner', 'reports', 'parents'));
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
    View::redirect('?page=' . rawurlencode($page === 'create-stable' ? 'home' : $page));
}
