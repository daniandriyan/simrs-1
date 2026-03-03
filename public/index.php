<?php

declare(strict_types=1);

/**
 * SIMRS Modern - Main Entry Point
 * 
 * Modern Modular Healthcare Information System
 * 
 * @package SIMRS\Modern
 * @author Healthcare System Team
 * @version 1.0.0
 */

// ============================================
// DEFINE BASE PATH
// ============================================
define('BASE_PATH', dirname(__DIR__));

// ============================================
// LOAD COMPOSER AUTOLOADER
// ============================================
require_once BASE_PATH . '/vendor/autoload.php';

// ============================================
// BOOTSTRAP APPLICATION
// ============================================
use App\Core\App;

try {
    // Get application instance and bootstrap
    $app = App::getInstance();
    $app->bootstrap();
    
    // Run the application
    $app->run();
    
} catch (Throwable $e) {
    // Fatal error handling
    if (env('APP_DEBUG', false)) {
        echo '<h1>Fatal Error</h1>';
        echo '<p><strong>' . htmlspecialchars(get_class($e)) . ':</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p>File: ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo '<h1>500 - Internal Server Error</h1>';
        echo '<p>An unexpected error occurred. Please contact the administrator.</p>';
    }
    
    // Log the error
    error_log(sprintf(
        '[FATAL] %s: %s in %s:%d',
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
}
