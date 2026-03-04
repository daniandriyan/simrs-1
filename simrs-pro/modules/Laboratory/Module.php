<?php
declare(strict_types=1);

namespace Modules\Laboratory;

use App\Core\BaseModule;
use App\Core\Router;
use Modules\Laboratory\Controllers\LaboratoryController;

class Module extends BaseModule
{
    public function getName(): string { return 'Laboratorium'; }
    public function getIcon(): string { return 'bi-microscope'; }

    public function getMenu(): array
    {
        return [
            ['label' => 'Antrian Order', 'url' => '/laboratory'],
            ['label' => 'Riwayat Hasil', 'url' => '/laboratory/history'],
            ['label' => 'Master Tes', 'url' => '/laboratory/tests']
        ];
    }

    public function getRoutes(Router $router): void
    {
        $router->get('/laboratory', [LaboratoryController::class, 'index']);
        $router->get('/laboratory/input/:int', [LaboratoryController::class, 'input']);
        $router->post('/laboratory/save', [LaboratoryController::class, 'save']);
    }
}
