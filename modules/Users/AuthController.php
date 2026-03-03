<?php

declare(strict_types=1);

namespace Modules\Users;

use App\Core\BaseController;

/**
 * Authentication Controller
 * 
 * Handles user login, logout, and authentication.
 * Implements secure authentication with password hashing,
 * CSRF protection, session regeneration, and login attempt limiting.
 */
class AuthController extends BaseController
{
    /**
     * Maximum login attempts before lockout
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Lockout duration in minutes
     */
    private const LOCKOUT_DURATION = 10;

    /**
     * Show login page
     *
     * @return string
     */
    public function showLogin(): string
    {
        // Redirect if already authenticated
        if (is_authenticated()) {
            redirect(base_url('admin/dashboard'));
        }

        return $this->view('auth/login.html', [
            'page_title' => 'Login',
            'csrf_token' => csrf_token()
        ]);
    }

    /**
     * Process login
     *
     * @return string
     */
    public function login(): string
    {
        // Redirect if already authenticated
        if (is_authenticated()) {
            redirect(base_url('admin/dashboard'));
        }

        // Verify CSRF token
        if (!csrf_verify()) {
            $this->flash('error', 'Token keamanan tidak valid. Silakan coba lagi.');
            redirect(base_url('login'));
        }

        $username = sanitize($this->input('username', ''));
        $password = $this->input('password', '');
        $remember = $this->input('remember', false);

        // Validation
        if (empty($username) || empty($password)) {
            $this->flash('error', 'Username dan password harus diisi.');
            redirect(base_url('login'));
        }

        // Check login attempts
        if ($this->isLockedOut()) {
            $remainingTime = $this->getLockoutRemainingTime();
            $this->flash('error', sprintf(
                'Terlalu banyak percobaan login. Silakan coba lagi dalam %d menit.',
                $remainingTime
            ));
            redirect(base_url('login'));
        }

        // Find user
        $user = $this->db('users')
            ->where('username', $username)
            ->where('status', 1)
            ->first();

        // Invalid credentials
        if (!$user || !password_verify($password, $user['password'])) {
            $this->recordFailedAttempt();
            $this->flash('error', 'Username atau password salah.');
            redirect(base_url('login'));
        }

        // Check if user has access
        if (!$this->hasUserAccess($user)) {
            $this->flash('error', 'Akun Anda tidak memiliki akses ke sistem ini.');
            redirect(base_url('login'));
        }

        // Successful login - regenerate session
        $this->performLogin($user, (bool) $remember);

        // Update last login
        $this->db('users')
            ->where('id', $user['id'])
            ->update([
                'last_login' => date('Y-m-d H:i:s'),
                'otp_code' => null,
                'otp_expires' => null
            ]);

        // Clear login attempts
        $this->clearLoginAttempts();

        // Log activity
        $this->logLoginActivity($user['id'], 'Login successful');

        // Redirect based on role
        $redirectUrl = $this->getRedirectUrl($user['role']);
        redirect($redirectUrl);

        return '';
    }

    /**
     * Process logout
     *
     * @return void
     */
    public function logout(): void
    {
        if (is_authenticated()) {
            // Log logout activity
            $this->logLoginActivity($this->userId(), 'Logout');

            // Delete remember token if exists
            if (isset($_COOKIE['remember_token'])) {
                $this->db('remember_tokens')
                    ->where('token', $_COOKIE['remember_token'])
                    ->delete();
                setcookie('remember_token', '', time() - 3600, '/');
            }
        }

        // Destroy session
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();

        // Regenerate CSRF token for next session
        csrf_regenerate();

        redirect(base_url('login'));
    }

    /**
     * Perform login - set session variables
     *
     * @param array $user User data
     * @param bool $remember Remember me option
     * @return void
     */
    private function performLogin(array $user, bool $remember): void
    {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['fullname'] = $user['fullname'] ?? $user['username'];
        $_SESSION['email'] = $user['email'] ?? null;
        $_SESSION['access'] = $user['access'] ?? null;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['ip_address'] = client_ip();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Remember me functionality
        if ($remember) {
            $this->createRememberToken($user['id']);
        }
    }

    /**
     * Create remember me token
     *
     * @param int $userId User ID
     * @return void
     */
    private function createRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days

        // Store in database
        $this->db('remember_tokens')->insert([
            'user_id' => $userId,
            'token' => $token,
            'expiry' => $expiry
        ]);

        // Set cookie
        setcookie('remember_token', $token, $expiry, '/', '', is_secure(), true);
    }

    /**
     * Check if user is locked out
     *
     * @return bool
     */
    private function isLockedOut(): bool
    {
        $ip = client_ip();
        $attempt = $this->db('login_attempts')
            ->where('ip', $ip)
            ->first();

        if (!$attempt) {
            return false;
        }

        // Check if lockout has expired
        if ($attempt['expires'] > 0 && time() < $attempt['expires']) {
            return true;
        }

        // Clear expired lockout
        if ($attempt['expires'] > 0 && time() >= $attempt['expires']) {
            $this->clearLoginAttempts();
        }

        return false;
    }

    /**
     * Get remaining lockout time in minutes
     *
     * @return int
     */
    private function getLockoutRemainingTime(): int
    {
        $attempt = $this->db('login_attempts')
            ->where('ip', client_ip())
            ->first();

        if (!$attempt || $attempt['expires'] <= 0) {
            return 0;
        }

        $remaining = $attempt['expires'] - time();
        return (int) ceil($remaining / 60);
    }

    /**
     * Record failed login attempt
     *
     * @return void
     */
    private function recordFailedAttempt(): void
    {
        $ip = client_ip();
        $attempt = $this->db('login_attempts')
            ->where('ip', $ip)
            ->first();

        if ($attempt) {
            $newAttempts = $attempt['attempts'] + 1;
            $expires = 0;

            // Lockout after MAX_ATTEMPTS
            if ($newAttempts >= self::MAX_ATTEMPTS) {
                $expires = time() + (self::LOCKOUT_DURATION * 60);
                $this->logLoginActivity(null, "Account locked: {$ip}");
            }

            $this->db('login_attempts')
                ->where('ip', $ip)
                ->update([
                    'attempts' => $newAttempts,
                    'expires' => $expires
                ]);
        } else {
            $this->db('login_attempts')->insert([
                'ip' => $ip,
                'attempts' => 1,
                'expires' => 0
            ]);
        }
    }

    /**
     * Clear login attempts for current IP
     *
     * @return void
     */
    private function clearLoginAttempts(): void
    {
        $this->db('login_attempts')
            ->where('ip', client_ip())
            ->delete();
    }

    /**
     * Check if user has system access
     *
     * @param array $user User data
     * @return bool
     */
    private function hasUserAccess(array $user): bool
    {
        // Admin has full access
        if ($user['role'] === 'admin') {
            return true;
        }

        // Check access field for specific modules
        $access = $user['access'] ?? '';
        if (empty($access)) {
            return false;
        }

        return true;
    }

    /**
     * Get redirect URL based on user role
     *
     * @param string $role User role
     * @return string
     */
    private function getRedirectUrl(string $role): string
    {
        return match ($role) {
            'admin' => base_url('admin/dashboard'),
            'dokter' => base_url('admin/dokter/dashboard'),
            'perawat' => base_url('admin/perawat/dashboard'),
            'farmasi' => base_url('admin/farmasi/dashboard'),
            'lab' => base_url('admin/lab/dashboard'),
            'rad' => base_url('admin/rad/dashboard'),
            'kasir' => base_url('admin/kasir/dashboard'),
            default => base_url('admin/dashboard')
        };
    }

    /**
     * Log login activity
     *
     * @param int|null $userId User ID
     * @param string $action Action description
     * @return void
     */
    private function logLoginActivity(?int $userId, string $action): void
    {
        $this->db('module_logs')->insert([
            'module' => 'users',
            'user_id' => $userId,
            'username' => $userId ? $this->db('users')->where('id', $userId)->value('username') : 'anonymous',
            'action' => $action,
            'context' => json_encode([
                'ip' => client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]),
            'ip_address' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Check if remember me token is valid
     *
     * @return array|null User data or null
     */
    public function checkRememberToken(): ?array
    {
        if (!isset($_COOKIE['remember_token'])) {
            return null;
        }

        $token = $_COOKIE['remember_token'];
        $remember = $this->db('remember_tokens')
            ->leftJoin('users', 'users.id=remember_tokens.user_id')
            ->where('remember_tokens.token', $token)
            ->where('remember_tokens.expiry', '>', time())
            ->where('users.status', 1)
            ->select(['users.*', 'token_id' => 'remember_tokens.id', 'expiry' => 'remember_tokens.expiry'])
            ->first();

        if (!$remember) {
            // Invalid or expired token
            if (isset($_COOKIE['remember_token'])) {
                setcookie('remember_token', '', time() - 3600, '/');
            }
            return null;
        }

        // Extend token expiry
        $newExpiry = time() + (30 * 24 * 60 * 60);
        $this->db('remember_tokens')
            ->where('id', $remember['token_id'])
            ->update(['expiry' => $newExpiry]);

        setcookie('remember_token', $token, $newExpiry, '/', '', is_secure(), true);

        return $remember;
    }

    /**
     * Change password
     *
     * @param int $userId User ID
     * @param string $oldPassword Old password
     * @param string $newPassword New password
     * @return array Result
     */
    public function changePassword(int $userId, string $oldPassword, string $newPassword): array
    {
        $user = $this->db('users')->where('id', $userId)->first();

        if (!$user) {
            return ['success' => false, 'message' => 'User tidak ditemukan'];
        }

        if (!password_verify($oldPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Password lama salah'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'Password minimal 8 karakter'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        $this->db('users')
            ->where('id', $userId)
            ->update([
                'password' => $hashedPassword,
                'password_changed_at' => date('Y-m-d H:i:s')
            ]);

        $this->logLoginActivity($userId, 'Password changed');

        return ['success' => true, 'message' => 'Password berhasil diubah'];
    }

    /**
     * Request password reset OTP
     *
     * @param string $username Username or email
     * @return array Result
     */
    public function requestPasswordReset(string $username): array
    {
        $user = $this->db('users')
            ->whereRaw('(username = ? OR email = ?)', [$username, $username])
            ->first();

        if (!$user) {
            // Don't reveal if user exists
            return ['success' => true, 'message' => 'Jika akun Anda terdaftar, Anda akan menerima email reset password'];
        }

        // Generate OTP
        $otp = sprintf('%06d', random_int(0, 999999));
        $expires = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 minutes

        $this->db('users')
            ->where('id', $user['id'])
            ->update([
                'otp_code' => $otp,
                'otp_expires' => $expires
            ]);

        // TODO: Send email with OTP
        // For now, log it (remove in production)
        log_message('info', "Password reset OTP for {$username}: {$otp}", 'auth');

        return ['success' => true, 'message' => 'Jika akun Anda terdaftar, Anda akan menerima email reset password'];
    }

    /**
     * Verify OTP and reset password
     *
     * @param string $username Username
     * @param string $otp OTP code
     * @param string $newPassword New password
     * @return array Result
     */
    public function resetPasswordWithOtp(string $username, string $otp, string $newPassword): array
    {
        $user = $this->db('users')
            ->where('username', $username)
            ->where('otp_code', $otp)
            ->where('otp_expires', '>', date('Y-m-d H:i:s'))
            ->first();

        if (!$user) {
            return ['success' => false, 'message' => 'Kode OTP tidak valid atau sudah kadaluarsa'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'Password minimal 8 karakter'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        $this->db('users')
            ->where('id', $user['id'])
            ->update([
                'password' => $hashedPassword,
                'password_changed_at' => date('Y-m-d H:i:s'),
                'otp_code' => null,
                'otp_expires' => null
            ]);

        $this->logLoginActivity($user['id'], 'Password reset via OTP');

        return ['success' => true, 'message' => 'Password berhasil direset'];
    }
}
