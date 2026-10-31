<?php
// The Bank: send Cash to another stable and see your Cash history.
use QuarterPole\Bank;
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_stable();

$bank = new Bank($db);
if ($isPost) {
    View::flash($bank->send($member->id, (string) ($_POST['to'] ?? ''), (string) ($_POST['amount'] ?? ''), (string) ($_POST['note'] ?? '')));
    View::redirect('bank.php');
}
View::render('bank', $shared + ['history' => $bank->history($member->id), 'others' => $bank->otherStables($member->id)]);
