<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base Module Class
 * 
 * All modules should extend this class.
 * Provides module lifecycle methods and common functionality.
 */
abstract class BaseModule extends BaseController
{
    /**
     * @var string Module directory name
     */
    protected string $dir = '';

    /**
     * @var array Module metadata
     */
    protected array $info = [];

    /**
     * @var bool Module is installed
     */
    protected bool $installed = false;

    /**
     * @var string Module version
     */
    protected string $version = '1.0.0';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        // Set module directory from class name
        $className = static::class;
        $parts = explode('\\', $className);
        $this->dir = strtolower($parts[1] ?? 'unknown');
        
        // Load module info
        $this->loadInfo();
    }

    /**
     * Load module information from Info.php
     *
     * @return void
     */
    protected function loadInfo(): void
    {
        $infoFile = modules_path("{$this->dir}/Info.php");
        
        if (file_exists($infoFile)) {
            $this->info = include $infoFile;
            $this->version = $this->info['version'] ?? '1.0.0';
        }
    }

    /**
     * Get module directory name
     *
     * @return string
     */
    public function getDir(): string
    {
        return $this->dir;
    }

    /**
     * Get module information
     *
     * @param string|null $key Specific info key (optional)
     * @return mixed
     */
    public function getInfo(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->info;
        }
        return $this->info[$key] ?? null;
    }

    /**
     * Get module version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Module initialization - called when module is loaded
     * Override this method to initialize module-specific functionality.
     *
     * @return void
     */
    public function init(): void
    {
        // Override in module if needed
    }

    /**
     * Module finish - called after request is processed
     * Override this method for cleanup or logging.
     *
     * @return void
     */
    public function finish(): void
    {
        // Override in module if needed
    }

    /**
     * Module installation
     * Override this method to create tables or seed data.
     *
     * @return bool
     */
    public function install(): bool
    {
        // Override in module to create tables
        return true;
    }

    /**
     * Module uninstallation
     * Override this method to drop tables or clean up data.
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        // Override in module to drop tables
        return true;
    }

    /**
     * Module activation
     * Called when module is enabled.
     *
     * @return bool
     */
    public function activate(): bool
    {
        return true;
    }

    /**
     * Module deactivation
     * Called when module is disabled.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        return true;
    }

    /**
     * Module upgrade
     * Override this method to handle version upgrades.
     *
     * @param string $fromVersion Version upgrading from
     * @param string $toVersion Version upgrading to
     * @return bool
     */
    public function upgrade(string $fromVersion, string $toVersion): bool
    {
        // Override in module to handle upgrades
        return true;
    }

    /**
     * Check if module is installed
     *
     * @return bool
     */
    public function isInstalled(): bool
    {
        // Check if module has installation record
        $module = $this->db('modules')->where('dir', $this->dir)->first();
        return $module !== null;
    }

    /**
     * Check if module is active/enabled
     *
     * @return bool
     */
    public function isActive(): bool
    {
        $module = $this->db('modules')->where('dir', $this->dir)->first();
        return $module !== null && (int) $module['status'] === 1;
    }

    /**
     * Get module setting
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = $this->db('module_settings')
            ->where('module', $this->dir)
            ->where('key', $key)
            ->first();
        
        return $setting !== null ? $setting['value'] : $default;
    }

    /**
     * Set module setting
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool
     */
    protected function setSetting(string $key, mixed $value): bool
    {
        $existing = $this->db('module_settings')
            ->where('module', $this->dir)
            ->where('key', $key)
            ->first();
        
        if ($existing) {
            return $this->db('module_settings')
                ->where('module', $this->dir)
                ->where('key', $key)
                ->update(['value' => (string) $value]) > 0;
        }
        
        return $this->db('module_settings')->insert([
            'module' => $this->dir,
            'key' => $key,
            'value' => (string) $value
        ]) > 0;
    }

    /**
     * Get all module settings
     *
     * @return array
     */
    protected function getSettings(): array
    {
        $settings = $this->db('module_settings')
            ->where('module', $this->dir)
            ->get();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['key']] = $setting['value'];
        }
        
        return $result;
    }

    /**
     * Log module activity
     *
     * @param string $action Action description
     * @param array $context Additional context
     * @return void
     */
    protected function log(string $action, array $context = []): void
    {
        $userId = $this->userId();
        $userName = $this->user()['username'] ?? 'system';
        
        $this->db('module_logs')->insert([
            'module' => $this->dir,
            'user_id' => $userId,
            'username' => $userName,
            'action' => $action,
            'context' => !empty($context) ? json_encode($context) : null,
            'ip_address' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get module navigation items
     * Override this method to define admin menu items.
     *
     * @return array
     */
    public function navigation(): array
    {
        return [];
    }

    /**
     * Get module permissions
     * Override this method to define module permissions.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [];
    }

    /**
     * Register module routes
     * Override this method to register custom routes.
     *
     * @param Router $router Router instance
     * @return void
     */
    public function routes(Router $router): void
    {
        // Override in module to register routes
    }

    /**
     * Get module view path
     *
     * @return string
     */
    protected function viewPath(): string
    {
        return modules_path("{$this->dir}/view");
    }

    /**
     * Draw module view
     *
     * @param string $view View file name
     * @param array $data Data to pass to view
     * @return string Rendered HTML
     */
    protected function draw(string $view, array $data = []): string
    {
        $viewPath = $this->viewPath() . '/' . $view;
        
        $template = new \App\Libraries\Template();
        return $template->render($viewPath, array_merge($this->data, $data));
    }

    /**
     * Get asset URL for module
     *
     * @param string $path Asset path
     * @return string
     */
    protected function asset(string $path): string
    {
        return base_url("modules/{$this->dir}/assets/" . ltrim($path, '/'));
    }

    /**
     * Check if user has module permission
     *
     * @param string $permission Permission name
     * @return bool
     */
    protected function hasPermission(string $permission): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        // Admin has all permissions
        if ($user['role'] === 'admin') {
            return true;
        }
        
        // Check module-specific permissions
        $modulePermissions = $user['module_permissions'][$this->dir] ?? [];
        return in_array($permission, $modulePermissions, true);
    }

    /**
     * Require module permission
     *
     * @param string $permission Permission name
     * @param string $message Custom error message
     * @return void
     */
    protected function requirePermission(string $permission, string $message = ''): void
    {
        if (!$this->hasPermission($permission)) {
            $message = $message ?: 'Anda tidak memiliki izin untuk mengakses fitur ini.';
            
            if ($this->isAjax()) {
                $this->error($message, 403);
                exit;
            }
            
            $this->flash('error', $message);
            $this->back();
        }
    }

    /**
     * Get module database table prefix
     *
     * @return string
     */
    protected function tablePrefix(): string
    {
        return $this->dir . '_';
    }

    /**
     * Get table name with module prefix
     *
     * @param string $table Table name
     * @return string
     */
    protected function table(string $table): string
    {
        return $this->tablePrefix() . $table;
    }
}
