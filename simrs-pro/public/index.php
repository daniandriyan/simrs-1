<?php
declare(strict_types=1);

// Simple Autoloader PSR-4
spl_autoload_register(function ($class) {
    $prefix = '';
    $base_dir = __DIR__ . '/../';

    $file = $base_dir . str_replace('', '/', $class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Application;

$app = new Application(dirname(__DIR__));

// Global Helper for sidebar
$GLOBALS['app'] = $app;

// Load Core Routes
require_once __DIR__ . '/../app/Config/routes.php';

$app->run();
