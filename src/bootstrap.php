<?php
declare(strict_types=1);

// Loads config, starts the session and wires up the classes every page needs.

define('QP_ROOT', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $prefix = 'QuarterPole\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $path = QP_ROOT . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

require QP_ROOT . '/src/helpers.php';

$configFile = QP_ROOT . '/config.local.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Missing config.local.php. Copy config.example.php to config.local.php and fill it in.');
}
$config = require $configFile;
date_default_timezone_set($config['timezone'] ?? 'America/New_York');

// With the forum login, SMF's SSI.php must load here: at global scope (it relies on globals)
// and before any session starts, so the game shares SMF's session. Game globals avoid SMF's
// names ($settings, $context, $user_info, $modSettings).
if (($config['auth'] ?? 'dev') === 'smf' && PHP_SAPI !== 'cli') {
    if (!is_file($config['smf_ssi_path'])) {
        http_response_code(500);
        exit('SMF SSI.php not found at the smf_ssi_path in config.local.php.');
    }
    require_once $config['smf_ssi_path'];
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db       = new QuarterPole\Db($config['db']);
$gameSettings = new QuarterPole\Settings($db);
$auth     = QuarterPole\Auth\AuthFactory::make($config);
$clock    = new QuarterPole\GameClock($db);
