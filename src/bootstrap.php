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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db       = new QuarterPole\Db($config['db']);
$settings = new QuarterPole\Settings($db);
$auth     = QuarterPole\Auth\AuthFactory::make($config);
$clock    = new QuarterPole\GameClock($db);
