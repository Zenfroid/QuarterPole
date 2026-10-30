<?php
declare(strict_types=1);

// Every page starts with: require __DIR__ . '/_boot.php';
// It loads the game, checks the login, and gives the page these variables:
//   $db, $gameSettings, $clock, $auth, $config  the game's services
//   $member  the logged-in forum member (or null)
//   $stable  their stable (or null)
//   $shared  the variables every template needs
// and these helpers: need_member(), need_stable(), need_admin(), post_only().

use QuarterPole\Csrf;
use QuarterPole\Stable;
use QuarterPole\UserError;
use QuarterPole\View;

// The game's code sits beside the web folder: in ../quarterpole/ on the host (next to public_html),
// or in .. when running from the repository.
require is_file(dirname(__DIR__) . '/quarterpole/src/bootstrap.php')
    ? dirname(__DIR__) . '/quarterpole/src/bootstrap.php'
    : dirname(__DIR__) . '/src/bootstrap.php';

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($isPost) {
    Csrf::check();
}

$member  = $auth->member();
$stables = new Stable($db, $gameSettings);
$stable  = $member ? $stables->find($member->id) : null;
$today   = $clock->today();
$shared  = ['config' => $config, 'member' => $member, 'stable' => $stable, 'auth' => $auth, 'today' => $today];

// A rule broken in a form (UserError) shows its message and sends the player back to the page they came from.
set_exception_handler(function (Throwable $e) {
    if (!$e instanceof UserError) {
        throw $e;
    }
    View::flash($e->getMessage(), 'error');
    View::redirect(back_url());
});

/** The page the player came from, on this site only. */
function back_url(string $fallback = 'index.php'): string
{
    $ref = parse_url($_SERVER['HTTP_REFERER'] ?? '');
    $host = ($ref['host'] ?? '') . (isset($ref['port']) ? ':' . $ref['port'] : '');
    if (!$ref || $host !== ($_SERVER['HTTP_HOST'] ?? '')) {
        return $fallback;
    }
    return basename($ref['path'] ?? '') . (isset($ref['query']) ? '?' . $ref['query'] : '') ?: $fallback;
}

/** Logged-out visitors go to the welcome page. */
function need_member(): void
{
    if (!$GLOBALS['member']) {
        View::redirect('index.php');
    }
}

/** Members without a stable go to the dashboard to open one. */
function need_stable(): void
{
    need_member();
    if (!$GLOBALS['stable']) {
        View::redirect('dashboard.php');
    }
}

/** Only forum admins get past this. */
function need_admin(): void
{
    need_member();
    if (!$GLOBALS['member']->isAdmin) {
        http_response_code(403);
        View::render('not_found', $GLOBALS['shared']);
        exit;
    }
}

/** For pages that only handle a form. */
function post_only(): void
{
    if (!$GLOBALS['isPost']) {
        View::redirect('dashboard.php');
    }
}
