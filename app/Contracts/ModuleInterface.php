<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Core\Router;
use App\Core\Database;
use Psr\Container\ContainerInterface;

/**
 * Module Interface
 * 
 * All modules must implement this interface.
 * Defines the contract for module lifecycle and registration.
 */
interface ModuleInterface
{
    /**
     * Get module metadata
     * 
     * @return array Module information
     */
    public function getInfo(): array;

    /**
     * Get module name (directory name)
     * 
     * @return string
     */
    public function getName(): string;

    /**
     * Get module version
     * 
     * @return string
     */
    public function getVersion(): string;

    /**
     * Register module routes
     * 
     * @param Router $router Router instance
     * @return void
     */
    public function registerRoutes(Router $router): void;

    /**
     * Register module services/providers
     * 
     * @param ContainerInterface $container Container instance
     * @return void
     */
    public function registerServices(ContainerInterface $container): void;

    /**
     * Boot module after all services are registered
     * 
     * @return void
     */
    public function boot(): void;

    /**
     * Get module navigation items for sidebar
     * 
     * @return array Navigation configuration
     */
    public function getNavigation(): array;

    /**
     * Get module permissions
     * 
     * @return array Permission definitions
     */
    public function getPermissions(): array;

    /**
     * Module initialization
     * Called when module is loaded
     * 
     * @return void
     */
    public function init(): void;

    /**
     * Module shutdown
     * Called after request is processed
     * 
     * @return void
     */
    public function shutdown(): void;

    /**
     * Check if module is installed
     * 
     * @return bool
     */
    public function isInstalled(): bool;

    /**
     * Check if module is active/enabled
     * 
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Install module
     * Create tables, seed data, etc.
     * 
     * @return bool
     */
    public function install(): bool;

    /**
     * Uninstall module
     * Remove tables, data, etc.
     * 
     * @return bool
     */
    public function uninstall(): bool;

    /**
     * Enable module
     * 
     * @return bool
     */
    public function enable(): bool;

    /**
     * Disable module
     * 
     * @return bool
     */
    public function disable(): bool;

    /**
     * Get module base path
     * 
     * @return string
     */
    public function getPath(): string;

    /**
     * Get module namespace
     * 
     * @return string
     */
    public function getNamespace(): string;

    /**
     * Render module view
     * 
     * @param string $view View name
     * @param array $data View data
     * @return string Rendered HTML
     */
    public function renderView(string $view, array $data = []): string;

    /**
     * Get database instance
     * 
     * @return Database
     */
    public function getDatabase(): Database;
}
