<?php
declare(strict_types=1);

/** @var App\Core\Router $router */
$router = $GLOBALS['app']->router;

$router->get('/', function() {
    header("Location: /dashboard");
    exit;
});
