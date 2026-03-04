<?php
declare(strict_types=1);

namespace Modules\EMR;

use App\Core\BaseModule;
use App\Core\Router;
use Modules\EMR\Controllers\EMRController;

class Module extends BaseModule
{
    public function getName(): string { return 'Pemeriksaan'; }
    public function getIcon(): string { return 'bi-activity'; }

    public function getMenu(): array
    {
        return [
            ['label' => 'Antrian Pasien', 'url' => '/emr']
        ];
    }

    public function getRoutes(Router $router): void
    {
        $router->get('/emr', [EMRController::class, 'index']);
        $router->get('/emr/examine/:int', [EMRController::class, 'examine']);
    }
}
