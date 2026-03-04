<?php
// Configuration File for Modern SIMRS
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'simrs_modern');
define('DB_PORT', '3306');

define('BASE_DIR', __DIR__);
define('URL_PATH', '/modern-simrs'); // Sesuaikan dengan path folder Anda
define('THEME_PATH', 'themes/default');
define('MOD_PATH', 'plugins');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();
