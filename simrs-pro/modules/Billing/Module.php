<?php
declare(strict_types=1);

namespace Modules\Billing;

use App\Core\BaseModule;
use App\Core\Router;
use Modules\Billing\Controllers\BillingController;

class Module extends BaseModule
{
    public function getName(): string { return 'Kasir'; }
    public function getIcon(): string { return 'bi-wallet2'; }

    public function getMenu(): array
    {
        return [
            ['label' => 'Antrian Kasir', 'url' => '/billing'],
            ['label' => 'Riwayat Transaksi', 'url' => '/billing/history']
        ];
    }

    public function getRoutes(Router $router): void
    {
        $router->get('/billing', [BillingController::class, 'index']);
        $router->get('/billing/pay/:int', [BillingController::class, 'pay']);
    }
}
