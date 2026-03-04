<?php
declare(strict_types=1);

namespace Modules\SatuSehat;

use App\Core\BaseModule;
use App\Core\Router;
use Modules\SatuSehat\Controllers\SatuSehatController;

class Module extends BaseModule
{
    public function getName(): string { return 'SatuSehat'; }
    public function getIcon(): string { return 'bi-cloud-check-fill'; }

    public function getMenu(): array
    {
        return [
            ['label' => 'Monitoring Sync', 'url' => '/satusehat']
        ];
    }

    public function getRoutes(Router $router): void
    {
        $router->get('/satusehat', [SatuSehatController::class, 'index']);
        $router->get('/satusehat/sync/:int', [SatuSehatController::class, 'syncPatient']);
    }
}
