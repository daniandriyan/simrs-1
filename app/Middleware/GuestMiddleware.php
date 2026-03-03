<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Guest Middleware
 * 
 * Redirects authenticated users away from login/register pages.
 * Only allows access to guests (unauthenticated users).
 */
class GuestMiddleware
{
    /**
     * Handle guest check
     *
     * @param mixed ...$params Route parameters
     * @return bool Continue execution or redirect
     */
    public function handle(mixed ...$params): bool
    {
        // Check if user is already authenticated
        if ($this->isAuthenticated()) {
            // For AJAX requests, return JSON
            if ($this->isAjax()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Already authenticated',
                    'redirect' => base_url('admin/dashboard')
                ]);
                exit;
            }

            // Redirect to dashboard
            redirect(base_url('admin/dashboard'));
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
        // Check session
        if (isset($_SESSION['logged_in']) && 
            $_SESSION['logged_in'] === true &&
            isset($_SESSION['user_id'])) {
            
            // Also check session validity
            return $this->isSessionValid();
        }

        // Check remember me
        return $this->checkRememberMe();
    }

    /**
     * Check session validity
     *
     * @return bool
     */
    private function isSessionValid(): bool
    {
        // Check session timeout
        $sessionTimeout = 2 * 60 * 60; // 2 hours
        if (isset($_SESSION['login_time']) && 
            (time() - $_SESSION['login_time']) > $sessionTimeout) {
            return false;
        }

        return true;
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
            ->first();

        return $remember !== null;
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
}
