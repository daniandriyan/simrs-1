<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\ModuleInterface;
use Psr\Container\ContainerInterface;

/**
 * Module Manager
 * 
 * Discovers, loads, and manages modules.
 * Handles module lifecycle and registration.
 */
class ModuleManager
{
    /**
     * @var array<string, ModuleInterface> Loaded modules
     */
    private array $modules = [];

    /**
     * @var array<string, array> Module metadata cache
     */
    private array $moduleInfo = [];

    /**
     * @var string Modules directory path
     */
    private string $modulesPath;

    /**
     * @var ContainerInterface Dependency injection container
     */
    private ContainerInterface $container;

    /**
     * @var Database Database instance
     */
    private Database $db;

    /**
     * @var array Module load order
     */
    private array $loadOrder = [];

    /**
     * Constructor
     *
     * @param string $modulesPath Path to modules directory
     * @param ContainerInterface $container DI container
     */
    public function __construct(string $modulesPath, ContainerInterface $container)
    {
        $this->modulesPath = $modulesPath;
        $this->container = $container;
        $this->db = new Database();
    }

    /**
     * Discover and load all modules
     *
     * @return self
     */
    public function discover(): self
    {
        if (!is_dir($this->modulesPath)) {
            return $this;
        }

        $directories = scandir($this->modulesPath);
        
        foreach ($directories as $dir) {
            // Skip dot directories and non-directories
            if (str_starts_with($dir, '.') || !is_dir($this->modulesPath . '/' . $dir)) {
                continue;
            }

            // Check if module has Module.php
            $moduleFile = $this->modulesPath . '/' . $dir . '/Module.php';
            
            if (file_exists($moduleFile)) {
                $this->loadOrder[] = $dir;
            }
        }

        // Sort modules by priority (if defined in info)
        $this->sortModulesByPriority();

        return $this;
    }

    /**
     * Load all discovered modules
     *
     * @return self
     */
    public function load(): self
    {
        foreach ($this->loadOrder as $moduleName) {
            try {
                $this->loadModule($moduleName);
            } catch (\Throwable $e) {
                log_message('error', "Failed to load module {$moduleName}: " . $e->getMessage(), 'modules');
            }
        }

        return $this;
    }

    /**
     * Boot all loaded modules
     *
     * @return self
     */
    public function boot(): self
    {
        foreach ($this->modules as $module) {
            try {
                $module->boot();
            } catch (\Throwable $e) {
                log_message('error', "Failed to boot module {$module->getName()}: " . $e->getMessage(), 'modules');
            }
        }

        return $this;
    }

    /**
     * Initialize all loaded modules
     *
     * @return self
     */
    public function init(): self
    {
        foreach ($this->modules as $module) {
            try {
                $module->init();
            } catch (\Throwable $e) {
                log_message('error', "Failed to init module {$module->getName()}: " . $e->getMessage(), 'modules');
            }
        }

        return $this;
    }

    /**
     * Shutdown all loaded modules
     *
     * @return self
     */
    public function shutdown(): self
    {
        foreach ($this->modules as $module) {
            try {
                $module->shutdown();
            } catch (\Throwable $e) {
                log_message('error', "Failed to shutdown module {$module->getName()}: " . $e->getMessage(), 'modules');
            }
        }

        return $this;
    }

    /**
     * Register routes for all modules
     *
     * @param Router $router Router instance
     * @return self
     */
    public function registerRoutes(Router $router): self
    {
        foreach ($this->modules as $module) {
            try {
                $module->registerRoutes($router);
            } catch (\Throwable $e) {
                log_message('error', "Failed to register routes for {$module->getName()}: " . $e->getMessage(), 'modules');
            }
        }

        return $this;
    }

    /**
     * Register services for all modules
     *
     * @return self
     */
    public function registerServices(): self
    {
        foreach ($this->modules as $module) {
            try {
                $module->registerServices($this->container);
            } catch (\Throwable $e) {
                log_message('error', "Failed to register services for {$module->getName()}: " . $e->getMessage(), 'modules');
            }
        }

        return $this;
    }

    /**
     * Load a single module
     *
     * @param string $name Module name
     * @return ModuleInterface|null
     */
    public function loadModule(string $name): ?ModuleInterface
    {
        if (isset($this->modules[$name])) {
            return $this->modules[$name];
        }

        $modulePath = $this->modulesPath . '/' . $name;
        $moduleFile = $modulePath . '/Module.php';

        if (!file_exists($moduleFile)) {
            throw new \RuntimeException("Module file not found: {$moduleFile}");
        }

        // Determine namespace from module name
        $namespace = "Modules\\{$name}";
        $className = $namespace . '\\Module';

        if (!class_exists($className)) {
            require_once $moduleFile;
        }

        if (!class_exists($className)) {
            throw new \RuntimeException("Module class not found: {$className}");
        }

        // Create module instance with dependency injection
        $module = new $className($this->container, $this->db, $modulePath, $namespace);

        if (!$module instanceof ModuleInterface) {
            throw new \RuntimeException("Module {$name} must implement ModuleInterface");
        }

        $this->modules[$name] = $module;
        $this->moduleInfo[$name] = $module->getInfo();

        return $module;
    }

    /**
     * Get a loaded module
     *
     * @param string $name Module name
     * @return ModuleInterface|null
     */
    public function getModule(string $name): ?ModuleInterface
    {
        return $this->modules[$name] ?? null;
    }

    /**
     * Check if module is loaded
     *
     * @param string $name Module name
     * @return bool
     */
    public function hasModule(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    /**
     * Get all loaded modules
     *
     * @return array<string, ModuleInterface>
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Get module info
     *
     * @param string $name Module name
     * @return array|null
     */
    public function getModuleInfo(string $name): ?array
    {
        return $this->moduleInfo[$name] ?? null;
    }

    /**
     * Get all module info
     *
     * @return array<string, array>
     */
    public function getAllModuleInfo(): array
    {
        return $this->moduleInfo;
    }

    /**
     * Get combined navigation from all modules
     *
     * @return array Combined navigation
     */
    public function getNavigation(): array
    {
        $navigation = [];

        foreach ($this->modules as $module) {
            if ($module->isActive()) {
                $nav = $module->getNavigation();
                if (!empty($nav)) {
                    $navigation[$module->getName()] = $nav;
                }
            }
        }

        return $navigation;
    }

    /**
     * Get combined permissions from all modules
     *
     * @return array Combined permissions
     */
    public function getPermissions(): array
    {
        $permissions = [];

        foreach ($this->modules as $module) {
            $perms = $module->getPermissions();
            if (!empty($perms)) {
                $permissions[$module->getName()] = $perms;
            }
        }

        return $permissions;
    }

    /**
     * Sort modules by priority
     *
     * @return void
     */
    private function sortModulesByPriority(): void
    {
        usort($this->loadOrder, function ($a, $b) {
            $infoA = $this->getModuleInfoFromFile($a);
            $infoB = $this->getModuleInfoFromFile($b);
            
            $priorityA = $infoA['priority'] ?? 100;
            $priorityB = $infoB['priority'] ?? 100;
            
            return $priorityA <=> $priorityB;
        });
    }

    /**
     * Get module info from file without loading
     *
     * @param string $name Module name
     * @return array
     */
    private function getModuleInfoFromFile(string $name): array
    {
        $infoFile = $this->modulesPath . '/' . $name . '/info.php';
        
        if (file_exists($infoFile)) {
            return include $infoFile;
        }

        return [];
    }

    /**
     * Enable a module
     *
     * @param string $name Module name
     * @return bool
     */
    public function enableModule(string $name): bool
    {
        $module = $this->getModule($name);
        
        if (!$module) {
            return false;
        }

        // Update database
        $this->db->table('modules')
            ->where('name', $name)
            ->update(['status' => 1]);

        return $module->enable();
    }

    /**
     * Disable a module
     *
     * @param string $name Module name
     * @return bool
     */
    public function disableModule(string $name): bool
    {
        $module = $this->getModule($name);
        
        if (!$module) {
            return false;
        }

        // Update database
        $this->db->table('modules')
            ->where('name', $name)
            ->update(['status' => 0]);

        return $module->disable();
    }

    /**
     * Install a module
     *
     * @param string $name Module name
     * @return bool
     */
    public function installModule(string $name): bool
    {
        $module = $this->loadModule($name);
        
        if (!$module) {
            return false;
        }

        $result = $module->install();

        if ($result) {
            // Record installation
            $this->db->table('modules')->insert([
                'name' => $name,
                'version' => $module->getVersion(),
                'status' => 1,
                'installed_at' => date('Y-m-d H:i:s')
            ]);
        }

        return $result;
    }

    /**
     * Uninstall a module
     *
     * @param string $name Module name
     * @return bool
     */
    public function uninstallModule(string $name): bool
    {
        $module = $this->getModule($name);
        
        if (!$module) {
            return false;
        }

        // Disable first
        $module->disable();

        $result = $module->uninstall();

        if ($result) {
            // Remove from database
            $this->db->table('modules')
                ->where('name', $name)
                ->delete();

            // Remove from loaded modules
            unset($this->modules[$name]);
            unset($this->moduleInfo[$name]);
        }

        return $result;
    }

    /**
     * Get modules path
     *
     * @return string
     */
    public function getModulesPath(): string
    {
        return $this->modulesPath;
    }

    /**
     * Get loaded modules count
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->modules);
    }
}
