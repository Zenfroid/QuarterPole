<?php
// Create a foundation horse with a creation credit.
use QuarterPole\HorseFactory;
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_stable();

if ($isPost) {
    $id = (new HorseFactory($db, $gameSettings, $clock))->createFoundation($member->id, [
        'name'    => (string) ($_POST['name'] ?? ''),
        'sex'     => (string) ($_POST['sex'] ?? ''),
        'age'     => (int) ($_POST['age'] ?? 0),
    ]);
    View::flash('Your new foundation horse has arrived.');
    View::redirect('horse.php?id=' . $id);
}
View::render('create_horse', $shared);
