<?php
declare(strict_types=1);
namespace Modules\Patient;
use App\Core\BaseModule;
use App\Core\Router;
use Modules\Patient\Controllers\PatientController;

class Module extends BaseModule {
    public function getName(): string { return 'Pasien'; }
    public function getIcon(): string { return 'bi-people-fill'; }
    public function getMenu(): array {
        return [
            ['label' => 'Data Pasien', 'url' => '/patient'],
            ['label' => 'Registrasi Pasien', 'url' => '/patient/create']
        ];
    }
    public function getRoutes(Router $router): void {
        $router->get('/patient', [PatientController::class, 'index']);
        $router->get('/patient/create', [PatientController::class, 'create']);
    }
}
