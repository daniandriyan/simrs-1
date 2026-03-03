<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Admin Middleware
 * 
 * Restricts access to admin routes based on user role.
 * Only allows users with 'admin' role.
 */
class AdminMiddleware
{
    /**
     * Handle admin authorization check
     *
     * @param mixed ...$params Route parameters
     * @return bool Continue execution or redirect
     */
    public function handle(mixed ...$params): bool
    {
        // First check if authenticated
        $authMiddleware = new AuthMiddleware();
        if (!$authMiddleware->handle(...$params)) {
            return false;
        }

        // Check if user has admin role
        if (!$this->isAdmin()) {
            // For AJAX requests, return 403
            if ($this->isAjax()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Access denied. Admin privileges required.',
                ]);
                exit;
            }

            // Redirect to dashboard with error
            flash('error', 'Akses ditolak. Anda tidak memiliki hak akses administrator.');
            redirect(base_url('admin/dashboard'));
        }

        return true;
    }

    /**
     * Check if user has admin role
     *
     * @return bool
     */
    private function isAdmin(): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
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
