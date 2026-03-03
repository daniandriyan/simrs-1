<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\ModuleInterface;
use App\Libraries\Template;
use Psr\Container\ContainerInterface;

/**
 * Abstract Module Base Class
 * 
 * Base class for all modules. Provides common functionality
 * and default implementations for ModuleInterface.
 */
abstract class Module implements ModuleInterface
{
    /**
     * @var ContainerInterface Dependency injection container
     */
    protected ContainerInterface $container;

    /**
     * @var Database Database instance
     */
    protected Database $db;

    /**
     * @var string Module base path
     */
    protected string $path;

    /**
     * @var string Module namespace
     */
    protected string $namespace;

    /**
     * @var string Module name
     */
    protected string $name;

    /**
     * @var array Module info cache
     */
    protected array $info = [];

    /**
     * @var Template Template engine instance
     */
    protected Template $template;

    /**
     * Constructor
     *
     * @param ContainerInterface $container DI container
     * @param Database $db Database instance
     * @param string $path Module base path
     * @param string $namespace Module namespace
     */
    public function __construct(
        ContainerInterface $container,
        Database $db,
        string $path,
        string $namespace
    ) {
        $this->container = $container;
        $this->db = $db;
        $this->path = $path;
        $this->namespace = $namespace;
        $this->template = new Template();
        
        // Extract module name from namespace
        $parts = explode('\\', $namespace);
        $this->name = end($parts);
        
        // Load module info
        $this->loadInfo();
    }

    /**
     * Load module information from info.php
     *
     * @return void
     */
    protected function loadInfo(): void
    {
        $infoFile = $this->path . '/info.php';
        
        if (file_exists($infoFile)) {
            $this->info = include $infoFile;
        }
        
        // Set defaults
        $this->info['name'] ??= $this->name;
        $this->info['version'] ??= '1.0.0';
        $this->info['priority'] ??= 100;
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return $this->info['version'] ?? '1.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function registerRoutes(Router $router): void
    {
        // Load routes from module's routes.php if exists
        $routesFile = $this->path . '/routes.php';
        
        if (file_exists($routesFile)) {
            $callback = require $routesFile;
            
            if (is_callable($callback)) {
                $callback($router, $this);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerServices(ContainerInterface $container): void
    {
        // Override in module to register services
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        // Override in module for boot logic
    }

    /**
     * {@inheritdoc}
     */
    public function getNavigation(): array
    {
        // Override in module to define navigation
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        // Override in module to define permissions
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        // Override in module for initialization logic
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(): void
    {
        // Override in module for shutdown logic
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled(): bool
    {
        $module = $this->db->table('modules')
            ->where('name', $this->name)
            ->first();
        
        return $module !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(): bool
    {
        $module = $this->db->table('modules')
            ->where('name', $this->name)
            ->where('status', 1)
            ->first();
        
        return $module !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function install(): bool
    {
        // Override in module to create tables, seed data, etc.
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): bool
    {
        // Override in module to remove tables, data, etc.
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function enable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function disable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function renderView(string $view, array $data = []): string
    {
        $viewPath = $this->path . '/views/' . $view;
        
        // Add .html extension if not present
        if (!str_ends_with($viewPath, '.html') && !str_ends_with($viewPath, '.php')) {
            $viewPath .= '.html';
        }
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$viewPath}");
        }
        
        return $this->template->render($viewPath, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(): Database
    {
        return $this->db;
    }

    /**
     * Get a service from container
     *
     * @param string $id Service ID
     * @return mixed
     */
    protected function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    /**
     * Check if service exists in container
     *
     * @param string $id Service ID
     * @return bool
     */
    protected function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * Get module URL
     *
     * @param string $path URL path
     * @return string
     */
    protected function url(string $path = ''): string
    {
        $basePath = strtolower($this->name);
        return base_url('admin/' . $basePath . '/' . ltrim($path, '/'));
    }

    /**
     * Get module asset URL
     *
     * @param string $path Asset path
     * @return string
     */
    protected function asset(string $path): string
    {
        return base_url('modules/' . $this->name . '/assets/' . ltrim($path, '/'));
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
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'system';
        
        $this->db->table('module_logs')->insert([
            'module' => $this->name,
            'user_id' => $userId,
            'username' => $username,
            'action' => $action,
            'context' => !empty($context) ? json_encode($context) : null,
            'ip_address' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
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
        $setting = $this->db->table('module_settings')
            ->where('module', $this->name)
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
        $existing = $this->db->table('module_settings')
            ->where('module', $this->name)
            ->where('key', $key)
            ->first();
        
        if ($existing) {
            return $this->db->table('module_settings')
                ->where('module', $this->name)
                ->where('key', $key)
                ->update(['value' => (string) $value]) > 0;
        }
        
        return $this->db->table('module_settings')->insert([
            'module' => $this->name,
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
        $settings = $this->db->table('module_settings')
            ->where('module', $this->name)
            ->get();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['key']] = $setting['value'];
        }
        
        return $result;
    }
}
