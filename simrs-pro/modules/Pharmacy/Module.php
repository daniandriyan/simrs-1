<?php
declare(strict_types=1);

namespace Modules\Pharmacy;

use App\Core\BaseModule;
use App\Core\Router;
use Modules\Pharmacy\Controllers\PharmacyController;

class Module extends BaseModule
{
    public function getName(): string { return 'Apotek'; }
    public function getIcon(): string { return 'bi-capsule'; }

    public function getMenu(): array
    {
        return [
            ['label' => 'Antrian Resep', 'url' => '/pharmacy'],
            ['label' => 'Stok Obat', 'url' => '/pharmacy/medicines']
        ];
    }

    public function getRoutes(Router $router): void
    {
        $router->get('/pharmacy', [PharmacyController::class, 'index']);
        $router->get('/pharmacy/medicines', [PharmacyController::class, 'medicines']);
    }
}
