<?php
// Copy this file to config.local.php and fill it in. config.local.php is not committed.
return [
    'db' => [
        'host'    => 'localhost',
        'name'    => 'quarterpole',
        'user'    => 'quarterpole',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    // 'dev' lets you log in as any member id without a forum (only works on your own computer).
    // 'smf' uses the SMF forum's login through SSI.php. Use 'smf' on the live site.
    'auth' => 'dev',
    // Path on disk to SMF's SSI.php, used when auth is 'smf'. On a host where the game's
    // public files sit in public_html next to this file, that is public_html/Forum/SSI.php.
    'smf_ssi_path' => __DIR__ . '/public/Forum/SSI.php',
    // Real-world day boundaries (training, interest) use this time zone.
    'timezone' => 'America/New_York',
    // Shown in page titles and the header.
    'site_name' => 'Quarter Pole',
];
