<?php

declare(strict_types=1);

namespace Modules\Users;

use App\Core\BaseModule;

/**
 * Users Admin Controller
 * 
 * Manages system users including CRUD operations,
 * role assignment, and access control.
 */
class Admin extends BaseModule
{
    /**
     * Module navigation
     *
     * @return array
     */
    public function navigation(): array
    {
        return [
            'Kelola User' => 'manage',
            'Roles' => 'roles',
        ];
    }

    /**
     * Users management page
     *
     * @return string
     */
    public function manage(): string
    {
        $this->requireAuth();
        $this->authorize($this->can('can_manage_users'), 'Anda tidak memiliki izin untuk mengelola user');

        // Pagination
        $page = (int) $this->input('page', 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Search
        $search = sanitize($this->input('q', ''));

        // Build query
        $query = $this->db('users');
        
        if (!empty($search)) {
            $query->whereRaw('(username LIKE ? OR fullname LIKE ? OR email LIKE ?)', [
                "%{$search}%", "%{$search}%", "%{$search}%"
            ]);
        }

        // Get total count
        $total = $query->count();

        // Get users
        $users = $query->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset($offset)
            ->get();

        // Roles list
        $roles = $this->db('roles')->get();

        return $this->draw('manage.html', [
            'users' => $users,
            'roles' => $roles,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ],
            'search' => $search,
            'page_title' => 'Manajemen User',
            'mlite_crud_permissions' => $this->loadCrudPermissions()
        ]);
    }

    /**
     * Show user form (create/edit)
     *
     * @param int|null $id User ID
     * @return string
     */
    public function form(?int $id = null): string
    {
        $this->requireAuth();
        $this->authorize($this->can('can_manage_users'), 'Anda tidak memiliki izin untuk mengelola user');

        $user = null;
        $roles = $this->db('roles')->get();

        if ($id !== null) {
            $user = $this->db('users')->where('id', $id)->first();
            if (!$user) {
                $this->flash('error', 'User tidak ditemukan');
                redirect(base_url('admin/users/manage'));
            }
        }

        return $this->draw('form.html', [
            'user' => $user,
            'roles' => $roles,
            'page_title' => $id ? 'Edit User' : 'Tambah User',
            'mlite_crud_permissions' => $this->loadCrudPermissions()
        ]);
    }

    /**
     * Save user (create/update)
     *
     * @return string JSON response
     */
    public function save(): string
    {
        $this->requireAuth();
        $this->authorize($this->can('can_manage_users'), 'Anda tidak memiliki izin untuk mengelola user');

        // CSRF check
        if (!csrf_verify()) {
            return $this->error('Token keamanan tidak valid', 403);
        }

        $id = (int) $this->input('id', 0);
        $username = sanitize($this->input('username', ''));
        $fullname = sanitize($this->input('fullname', ''));
        $email = sanitize($this->input('email', ''));
        $phone = sanitize($this->input('phone', ''));
        $role = $this->input('role', 'staff');
        $access = $this->input('access', []);
        $status = (int) $this->input('status', 1);
        $password = $this->input('password', '');
        $password_confirm = $this->input('password_confirm', '');

        // Validation
        $rules = [
            'username' => 'required|min_length:3|max_length:100|alpha_dash|unique:users,username' . ($id ? ',' . $id : ''),
            'fullname' => 'required|min_length:2|max_length:255',
            'email' => 'required|email|unique:users,email' . ($id ? ',' . $id : ''),
            'role' => 'required|in:admin,dokter,perawat,farmasi,lab,rad,kasir,admin_poli,staff',
            'status' => 'in:0,1',
        ];

        // Password required for new users
        if ($id === 0) {
            $rules['password'] = 'required|min_length:8';
        }

        if ($password !== '') {
            $rules['password_confirm'] = 'same:password';
        }

        if (!$this->validate($rules)) {
            return $this->error($this->firstError(), 400);
        }

        // Prepare data
        $data = [
            'username' => $username,
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
            'access' => is_array($access) ? implode(',', $access) : $access,
            'status' => $status,
        ];

        // Hash password for new users or if changed
        if (!empty($password)) {
            if ($password !== $password_confirm) {
                return $this->error('Konfirmasi password tidak cocok', 400);
            }
            $data['password'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $data['password_changed_at'] = date('Y-m-d H:i:s');
        }

        try {
            if ($id === 0) {
                // Create new user
                $data['created_at'] = date('Y-m-d H:i:s');
                $userId = $this->db('users')->insert($data);
                
                $this->log('User created', ['user_id' => $userId, 'username' => $username]);
                $this->flash('success', 'User berhasil ditambahkan');
            } else {
                // Update existing user
                $this->db('users')
                    ->where('id', $id)
                    ->update($data);
                
                $this->log('User updated', ['user_id' => $id, 'username' => $username]);
                $this->flash('success', 'User berhasil diperbarui');
            }

            return $this->success(['redirect' => base_url('admin/users/manage')]);

        } catch (\Throwable $e) {
            log_message('error', 'User save error: ' . $e->getMessage(), 'users');
            return $this->error('Gagal menyimpan user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete user
     *
     * @return string JSON response
     */
    public function delete(): string
    {
        $this->requireAuth();
        $this->authorize($this->can('can_manage_users'), 'Anda tidak memiliki izin untuk menghapus user');

        // CSRF check
        if (!csrf_verify()) {
            return $this->error('Token keamanan tidak valid', 403);
        }

        $id = (int) $this->input('id', 0);

        if ($id === 0) {
            return $this->error('ID user tidak valid', 400);
        }

        // Prevent deleting own account
        if ($id === $this->userId()) {
            return $this->error('Tidak dapat menghapus akun Anda sendiri', 400);
        }

        // Prevent deleting last admin
        $user = $this->db('users')->where('id', $id)->first();
        if (!$user) {
            return $this->error('User tidak ditemukan', 404);
        }

        if ($user['role'] === 'admin') {
            $adminCount = $this->db('users')
                ->where('role', 'admin')
                ->where('status', 1)
                ->count();
            
            if ($adminCount <= 1) {
                return $this->error('Tidak dapat menghapus admin terakhir', 400);
            }
        }

        try {
            // Soft delete - set status to inactive
            $this->db('users')
                ->where('id', $id)
                ->update(['status' => 0]);

            $this->log('User deleted (deactivated)', ['user_id' => $id, 'username' => $user['username']]);
            
            return $this->success(['message' => 'User berhasil dihapus']);

        } catch (\Throwable $e) {
            log_message('error', 'User delete error: ' . $e->getMessage(), 'users');
            return $this->error('Gagal menghapus user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reset user password
     *
     * @return string JSON response
     */
    public function resetPassword(): string
    {
        $this->requireAuth();
        $this->authorize($this->can('can_manage_users'), 'Anda tidak memiliki izin untuk mereset password user');

        // CSRF check
        if (!csrf_verify()) {
            return $this->error('Token keamanan tidak valid', 403);
        }

        $id = (int) $this->input('id', 0);
        $newPassword = $this->input('new_password', '');

        if ($id === 0) {
            return $this->error('ID user tidak valid', 400);
        }

        if (empty($newPassword)) {
            return $this->error('Password baru harus diisi', 400);
        }

        if (strlen($newPassword) < 8) {
            return $this->error('Password minimal 8 karakter', 400);
        }

        $user = $this->db('users')->where('id', $id)->first();
        if (!$user) {
            return $this->error('User tidak ditemukan', 404);
        }

        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

            $this->db('users')
                ->where('id', $id)
                ->update([
                    'password' => $hashedPassword,
                    'password_changed_at' => date('Y-m-d H:i:s')
                ]);

            $this->log('Password reset', ['user_id' => $id, 'username' => $user['username']]);
            
            return $this->success(['message' => 'Password berhasil direset']);

        } catch (\Throwable $e) {
            log_message('error', 'Password reset error: ' . $e->getMessage(), 'users');
            return $this->error('Gagal mereset password: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Toggle user status
     *
     * @return string JSON response
     */
    public function toggleStatus(): string
    {
        $this->requireAuth();
        $this->authorize($this->can('can_manage_users'), 'Anda tidak memiliki izin untuk mengubah status user');

        // CSRF check
        if (!csrf_verify()) {
            return $this->error('Token keamanan tidak valid', 403);
        }

        $id = (int) $this->input('id', 0);

        if ($id === 0) {
            return $this->error('ID user tidak valid', 400);
        }

        // Prevent deactivating own account
        if ($id === $this->userId()) {
            return $this->error('Tidak dapat menonaktifkan akun Anda sendiri', 400);
        }

        $user = $this->db('users')->where('id', $id)->first();
        if (!$user) {
            return $this->error('User tidak ditemukan', 404);
        }

        $newStatus = $user['status'] === 1 ? 0 : 1;

        try {
            $this->db('users')
                ->where('id', $id)
                ->update(['status' => $newStatus]);

            $this->log('User status toggled', [
                'user_id' => $id, 
                'username' => $user['username'],
                'new_status' => $newStatus
            ]);
            
            return $this->success([
                'message' => $newStatus === 1 ? 'User diaktifkan' : 'User dinonaktifkan',
                'status' => $newStatus
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'Status toggle error: ' . $e->getMessage(), 'users');
            return $this->error('Gagal mengubah status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Load CRUD permissions for current user
     *
     * @return array
     */
    private function loadCrudPermissions(): array
    {
        // Admin has full permissions
        if ($this->getUserInfo('role') === 'admin') {
            return [
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => true
            ];
        }

        // Check specific permission
        $canManage = $this->can('can_manage_users');
        
        return [
            'can_create' => $canManage,
            'can_read' => $canManage,
            'can_update' => $canManage,
            'can_delete' => $canManage
        ];
    }

    /**
     * Log user activity
     *
     * @param string $action Action description
     * @param array $context Additional context
     * @return void
     */
    private function log(string $action, array $context = []): void
    {
        $this->db('module_logs')->insert([
            'module' => 'users',
            'user_id' => $this->userId(),
            'username' => $this->getUserInfo('username'),
            'action' => $action,
            'context' => json_encode($context),
            'ip_address' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get user info helper
     *
     * @param string $field Field name
     * @return mixed
     */
    private function getUserInfo(string $field): mixed
    {
        return $_SESSION[$field] ?? null;
    }

    /**
     * Check permission helper
     *
     * @param string $permission Permission name
     * @return bool
     */
    private function can(string $permission): bool
    {
        // Admin has all permissions
        if ($this->getUserInfo('role') === 'admin') {
            return true;
        }

        // Check access field
        $access = $this->getUserInfo('access') ?? '';
        if ($access === 'all') {
            return true;
        }

        $accessArray = explode(',', $access);
        return in_array($permission, $accessArray);
    }
}
