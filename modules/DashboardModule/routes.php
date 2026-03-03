<?php

declare(strict_types=1);

/**
 * DashboardModule Routes
 * 
 * Register module routes.
 * 
 * @param \App\Core\Router $router Router instance
 * @param \Modules\DashboardModule\Module $module Module instance
 */

use App\Core\Router;
use Modules\DashboardModule\Module;

return function (Router $router, Module $module) {
    
    // Dashboard main page
    $router->get('admin/dashboard', function () use ($module) {
        return $module->getController()->index();
    });

    // API routes
    $router->get('admin/dashboard/api/stats', function () use ($module) {
        return $module->getController()->apiStats();
    });

    $router->get('admin/dashboard/api/activities', function () use ($module) {
        return $module->getController()->apiActivities();
    });

    $router->get('admin/dashboard/api/departments', function () use ($module) {
        return $module->getController()->apiDepartments();
    });

};
