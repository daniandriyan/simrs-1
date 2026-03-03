<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Auth Middleware
 * 
 * Protects routes by requiring authentication.
 * Redirects unauthenticated users to login page.
 */
class AuthMiddleware
{
    /**
     * Handle authentication check
     *
     * @param mixed ...$params Route parameters
     * @return bool Continue execution or redirect
     */
    public function handle(mixed ...$params): bool
    {
        // Check if user is authenticated
        if (!$this->isAuthenticated()) {
            // Check for remember me token
            if (!$this->checkRememberMe()) {
                // Store intended destination
                $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
                
                // For AJAX requests, return 401
                if ($this->isAjax()) {
                    http_response_code(401);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Unauthorized. Please login.',
                        'redirect' => base_url('login')
                    ]);
                    exit;
                }
                
                // Redirect to login
                flash('error', 'Silakan login untuk melanjutkan');
                redirect(base_url('login'));
            }
        }

        // Check session validity
        if (!$this->isSessionValid()) {
            $this->logout();
            flash('error', 'Sesi Anda telah kadaluarsa. Silakan login kembali.');
            redirect(base_url('login'));
        }

        // Check for forced password change (OTP required)
        if ($this->requiresPasswordChange()) {
            // Allow access to password change page only
            $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if (!str_contains($currentPath, 'change-password') && 
                !str_contains($currentPath, 'logout')) {
                redirect(base_url('admin/change-password'));
            }
        }

        return true;
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    private function isAuthenticated(): bool
    {
        return isset($_SESSION['logged_in']) && 
               $_SESSION['logged_in'] === true &&
               isset($_SESSION['user_id']);
    }

    /**
     * Check remember me token
     *
     * @return bool
     */
    private function checkRememberMe(): bool
    {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }

        $token = $_COOKIE['remember_token'];
        
        $db = \App\Core\App::getInstance()->getDatabase();
        $remember = $db->table('remember_tokens')
            ->leftJoin('users', 'users.id=remember_tokens.user_id')
            ->where('remember_tokens.token', $token)
            ->where('remember_tokens.expiry', '>', time())
            ->where('users.status', 1)
            ->select(['users.*', 'token_id' => 'remember_tokens.id'])
            ->first();

        if (!$remember) {
            setcookie('remember_token', '', time() - 3600, '/');
            return false;
        }

        // Restore session
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $remember['id'];
        $_SESSION['username'] = $remember['username'];
        $_SESSION['role'] = $remember['role'];
        $_SESSION['fullname'] = $remember['fullname'] ?? $remember['username'];
        $_SESSION['email'] = $remember['email'] ?? null;
        $_SESSION['access'] = $remember['access'] ?? null;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['ip_address'] = client_ip();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Extend token
        $newExpiry = time() + (30 * 24 * 60 * 60);
        $db->table('remember_tokens')
            ->where('id', $remember['token_id'])
            ->update(['expiry' => $newExpiry]);

        setcookie('remember_token', $token, $newExpiry, '/', '', is_secure(), true);

        return true;
    }

    /**
     * Check if session is still valid
     *
     * @return bool
     */
    private function isSessionValid(): bool
    {
        // Check session timeout (2 hours)
        $sessionTimeout = 2 * 60 * 60; // 2 hours
        if (isset($_SESSION['login_time']) && 
            (time() - $_SESSION['login_time']) > $sessionTimeout) {
            return false;
        }

        // Check user agent consistency (optional security)
        if (isset($_SESSION['user_agent']) && 
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            // Log potential session hijacking attempt
            log_message('warning', sprintf(
                'Potential session hijacking: User %d, Expected UA: %s, Got: %s',
                $_SESSION['user_id'] ?? 0,
                $_SESSION['user_agent'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT']
            ), 'security');
            return false;
        }

        // Check IP consistency (optional, can be disabled for mobile users)
        // if (isset($_SESSION['ip_address']) && 
        //     $_SESSION['ip_address'] !== client_ip()) {
        //     return false;
        // }

        // Update session activity time
        $_SESSION['login_time'] = time();

        return true;
    }

    /**
     * Check if user needs to change password
     *
     * @return bool
     */
    private function requiresPasswordChange(): bool
    {
        // Check if OTP is set (password reset requested)
        return isset($_SESSION['requires_password_change']) && 
               $_SESSION['requires_password_change'] === true;
    }

    /**
     * Check if request is AJAX
     *
     * @return bool
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Logout user
     *
     * @return void
     */
    private function logout(): void
    {
        // Delete remember token
        if (isset($_COOKIE['remember_token'])) {
            $db = \App\Core\App::getInstance()->getDatabase();
            $db->table('remember_tokens')
                ->where('token', $_COOKIE['remember_token'])
                ->delete();
            setcookie('remember_token', '', time() - 3600, '/');
        }

        // Log activity
        if (isset($_SESSION['user_id'])) {
            $db = \App\Core\App::getInstance()->getDatabase();
            $db->table('module_logs')->insert([
                'module' => 'users',
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? 'unknown',
                'action' => 'Session expired/invalid',
                'ip_address' => client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Destroy session
        $_SESSION = [];
        session_destroy();
    }
}
