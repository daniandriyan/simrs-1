<?php

declare(strict_types=1);

/**
 * SIMRS Modern - Helper Functions
 * 
 * Global helper functions available throughout the application.
 */

use App\Core\App;

/**
 * Get the root path of the application
 */
function base_path(string $path = ''): string
{
    return dirname(__DIR__) . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
}

/**
 * Get the public path
 */
function public_path(string $path = ''): string
{
    return base_path('public') . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
}

/**
 * Get the app path
 */
function app_path(string $path = ''): string
{
    return base_path('app') . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
}

/**
 * Get the storage path
 */
function storage_path(string $path = ''): string
{
    return base_path('storage') . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
}

/**
 * Get the views path
 */
function views_path(string $path = ''): string
{
    return base_path('views') . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
}

/**
 * Get the modules path
 */
function modules_path(string $path = ''): string
{
    return base_path('modules') . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
}

/**
 * Get configuration value
 */
function config(string $key, mixed $default = null): mixed
{
    return App::getInstance()->getConfig($key, $default);
}

/**
 * Get environment variable
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    // Handle boolean values
    if (is_string($value)) {
        $lowerValue = strtolower($value);
        if ($lowerValue === 'true' || $lowerValue === 'false') {
            return $lowerValue === 'true';
        }
        
        // Handle null values
        if ($lowerValue === 'null' || $lowerValue === 'empty') {
            return null;
        }
        
        // Handle numeric values
        if (is_numeric($value)) {
            return $value + 0;
        }
    }
    
    return $value;
}

/**
 * Get database connection
 */
function db(): App\Core\Database
{
    return App::getInstance()->getDatabase();
}

/**
 * Get router instance
 */
function router(): App\Core\Router
{
    return App::getInstance()->getRouter();
}

/**
 * Get session value
 */
function session_get(string $key, mixed $default = null): mixed
{
    if (!isset($_SESSION[$key])) {
        return $default;
    }
    return $_SESSION[$key];
}

/**
 * Set session value
 */
function session_set(string $key, mixed $value): void
{
    $_SESSION[$key] = $value;
}

/**
 * Flash message (stored in session for one-time use)
 */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][$type] = $message;
}

/**
 * Get and clear flash message
 */
function flash_get(string $type): ?string
{
    if (!isset($_SESSION['_flash'][$type])) {
        return null;
    }
    $message = $_SESSION['_flash'][$type];
    unset($_SESSION['_flash'][$type]);
    return $message;
}

/**
 * Redirect to URL
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Get current URL
 */
function current_url(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || 
                 (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return $protocol . $host . $uri;
}

/**
 * Get base URL
 */
function base_url(string $path = ''): string
{
    $baseUrl = env('APP_URL', 'http://localhost:8000');
    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}

/**
 * Get asset URL
 */
function asset(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

/**
 * Escape HTML output
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if request is AJAX
 */
function is_ajax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get request method
 */
function request_method(): string
{
    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

/**
 * Get POST data
 */
function post(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}

/**
 * Get GET data
 */
function get(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

/**
 * Generate CSRF token
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Get CSRF token input field
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verify CSRF token
 */
function csrf_verify(): bool
{
    if (!env('CSRF_ENABLED', true)) {
        return true;
    }
    
    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    if (empty($token) || empty($_SESSION['_csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['_csrf_token'], $token);
}

/**
 * Regenerate CSRF token
 */
function csrf_regenerate(): string
{
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf_token'];
}

/**
 * Check if user is authenticated
 */
function is_authenticated(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Get authenticated user ID
 */
function auth_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get authenticated user data
 */
function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Format date to Indonesian format
 */
function date_id(string $date, string $format = 'd F Y'): string
{
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $dateTime = new DateTime($date);
    $month = $months[(int) $dateTime->format('n')];
    $day = $dateTime->format('d');
    $year = $dateTime->format('Y');
    
    return str_replace(['F', 'd', 'Y'], [$month, $day, $year], $format);
}

/**
 * Get day name in Indonesian
 */
function day_id(string $date): string
{
    $days = [
        'Sun' => 'Minggu',
        'Mon' => 'Senin',
        'Tue' => 'Selasa',
        'Wed' => 'Rabu',
        'Thu' => 'Kamis',
        'Fri' => 'Jumat',
        'Sat' => 'Sabtu'
    ];
    
    $day = date('D', strtotime($date));
    return $days[$day];
}

/**
 * Format currency (Rupiah)
 */
function rupiah(int|float $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Log message to file
 */
function log_message(string $level, string $message, string $channel = 'app'): void
{
    $logFile = storage_path('logs/' . $channel . '_' . date('Y-m-d') . '.log');
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Debug helper (dd - dump and die)
 */
function dd(mixed ...$values): void
{
    echo '<pre>';
    foreach ($values as $value) {
        var_dump($value);
        echo PHP_EOL;
    }
    echo '</pre>';
    exit;
}

/**
 * Debug helper (dump)
 */
function dump(mixed ...$values): void
{
    echo '<pre>';
    foreach ($values as $value) {
        var_dump($value);
        echo PHP_EOL;
    }
    echo '</pre>';
}

/**
 * Generate unique ID
 */
function generate_id(string $prefix = ''): string
{
    $uniqueId = $prefix . bin2hex(random_bytes(8));
    return $uniqueId;
}

/**
 * Sanitize input
 */
function sanitize(string $data): string
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get client IP address
 */
function client_ip(): string
{
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
               'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return '0.0.0.0';
}

/**
 * Check if request is secure (HTTPS)
 */
function is_secure(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           ($_SERVER['SERVER_PORT'] ?? 80) == 443 ||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

/**
 * Get application instance
 */
function app(): App
{
    return App::getInstance();
}

/**
 * Check if current menu is active
 *
 * @param string $menu Menu name to check
 * @return bool
 */
function active_menu(string $menu): bool
{
    $currentUri = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($currentUri, $menu);
}

/**
 * Check if section exists in template
 *
 * @param string $section Section name
 * @return bool
 */
function yield_exists(string $section): bool
{
    // This is handled by the template engine
    return true;
}
