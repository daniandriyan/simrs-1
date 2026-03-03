<?php

declare(strict_types=1);

namespace Modules\DashboardModule;

use App\Core\Module;
use App\Core\Router;
use App\Core\Database;
use Psr\Container\ContainerInterface;

/**
 * DashboardModule
 * 
 * Main dashboard module providing statistics, charts, and quick access.
 * This is an example module demonstrating the plugin architecture.
 */
class Module extends Module
{
    /**
     * @var DashboardController Controller instance
     */
    private ?DashboardController $controller = null;

    /**
     * {@inheritdoc}
     */
    public function registerServices(ContainerInterface $container): void
    {
        // Register dashboard service
        $container->singleton('dashboard.service', function ($container) {
            return new DashboardService($container->get(Database::class));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function registerRoutes(Router $router): void
    {
        // Load routes from routes.php
        parent::registerRoutes($router);
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        // Boot logic - runs after all modules are loaded
        // Initialize dashboard cache, etc.
    }

    /**
     * {@inheritdoc}
     */
    public function getNavigation(): array
    {
        return [
            [
                'name' => 'Dashboard',
                'url' => '/admin/dashboard',
                'icon' => 'bi-speedometer2',
                'permission' => 'view_dashboard',
                'order' => 1,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return [
            'view_dashboard' => 'View Dashboard',
            'manage_dashboard' => 'Manage Dashboard Widgets',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        // Initialization logic
        $this->log('Module initialized');
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(): void
    {
        // Cleanup logic
    }

    /**
     * {@inheritdoc}
     */
    public function install(): bool
    {
        try {
            // Create dashboard-specific tables if needed
            // For now, dashboard doesn't need its own tables
            
            $this->log('Module installed');
            return true;
        } catch (\Throwable $e) {
            $this->log('Install failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): bool
    {
        try {
            // Remove dashboard tables if any
            $this->log('Module uninstalled');
            return true;
        } catch (\Throwable $e) {
            $this->log('Uninstall failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get dashboard controller
     *
     * @return DashboardController
     */
    public function getController(): DashboardController
    {
        if ($this->controller === null) {
            $this->controller = new DashboardController(
                $this->container,
                $this->db,
                $this->get('dashboard.service')
            );
        }
        
        return $this->controller;
    }
}
