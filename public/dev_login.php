<?php
// Test login, only on a development computer (config 'auth' => 'dev'). On the live site players log in on the forum.
use QuarterPole\Auth\DevAuth;
use QuarterPole\View;

require __DIR__ . '/_boot.php';

if (!$auth instanceof DevAuth) {
    View::redirect('index.php');
}
if (($_GET['do'] ?? '') === 'logout') {
    $auth->logout();
    View::redirect('index.php');
}
if ($isPost) {
    $auth->login(max(1, (int) $_POST['id']), trim($_POST['name']) ?: 'Tester', !empty($_POST['admin']));
    View::redirect('dashboard.php');
}
View::render('dev_login', $shared);
