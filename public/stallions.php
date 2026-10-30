<?php
// Stallions standing at stud, filtered by surface.
use QuarterPole\Breeding;
use QuarterPole\View;

require __DIR__ . '/_boot.php';
need_stable();

$surface = in_array($_GET['surface'] ?? '', ['dirt', 'turf', 'steeplechase'], true) ? $_GET['surface'] : null;
$studs = (new Breeding($db, $gameSettings, $clock))->studs($surface);
$bookCaps = ['game' => $gameSettings->int('book_cap_game'), 'player' => $gameSettings->int('book_cap_player')];
View::render('stallions', $shared + compact('studs', 'surface', 'bookCaps'));
