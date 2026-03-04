<?php
declare(strict_types=1);

namespace Modules\Dashboard;

use App\Core\BaseModule;
use App\Core\Router;
use Modules\Dashboard\Controllers\DashboardController;

class Module extends BaseModule
{
    public function getName(): string { return 'Dashboard'; }
    public function getIcon(): string { return 'bi-grid-1x2-fill'; }

    public function getMenu(): array
    {
        return [
            ['label' => 'Dashboard', 'url' => '/dashboard']
        ];
    }

    public function getRoutes(Router $router): void
    {
        $router->get('/dashboard', [DashboardController::class, 'index']);
    }
}
