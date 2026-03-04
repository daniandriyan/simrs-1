<?php
declare(strict_types=1);

namespace Modules\Registration;

use App\Core\BaseModule;
use App\Core\Router;
use Modules\Registration\Controllers\RegistrationController;

class Module extends BaseModule
{
    public function getName(): string { return 'Registrasi'; }
    public function getIcon(): string { return 'bi-clipboard2-check-fill'; }

    public function getMenu(): array
    {
        return [
            ['label' => 'Antrian Hari Ini', 'url' => '/registration'],
            ['label' => 'Pendaftaran Layanan', 'url' => '/registration/create']
        ];
    }

    public function getRoutes(Router $router): void
    {
        $router->get('/registration', [RegistrationController::class, 'index']);
        $router->get('/registration/create', [RegistrationController::class, 'create']);
    }
}
