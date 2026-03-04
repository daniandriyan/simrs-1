<?php
declare(strict_types=1);

namespace Modules\Reporting;

use App\Core\BaseModule;
use App\Core\Router;
use Modules\Reporting\Controllers\ReportController;

class Module extends BaseModule
{
    public function getName(): string { return 'Laporan'; }
    public function getIcon(): string { return 'bi-bar-chart-line-fill'; }

    public function getMenu(): array
    {
        return [
            ['label' => 'Executive Summary', 'url' => '/reporting']
        ];
    }

    public function getRoutes(Router $router): void
    {
        $router->get('/reporting', [ReportController::class, 'index']);
    }
}
