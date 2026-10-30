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
    // 'dev' lets you log in as any member id without a forum (local testing only).
    // 'smf' uses the SMF forum's login through SSI.php.
    'auth' => 'dev',
    // Path on disk to SMF's SSI.php, used when auth is 'smf'.
    'smf_ssi_path' => __DIR__ . '/public/Forum/SSI.php',
    // Real-world day boundaries (training, interest) use this time zone.
    'timezone' => 'America/New_York',
    // Shown in page titles and the header.
    'site_name' => 'Quarter Pole',
];
