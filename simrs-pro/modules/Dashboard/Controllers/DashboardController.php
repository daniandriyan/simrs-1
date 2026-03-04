<?php
declare(strict_types=1);

namespace Modules\Dashboard\Controllers;

use App\Core\BaseController;

class DashboardController extends BaseController
{
    public function index(): string
    {
        return $this->render('Dashboard', 'index', [
            'title' => 'Dashboard SIMRS Pro',
            'stats' => ['pasien' => 124, 'antrian' => 45]
        ]);
    }
}
