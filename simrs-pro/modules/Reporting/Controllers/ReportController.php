<?php
declare(strict_types=1);

namespace Modules\Reporting\Controllers;

use App\Core\BaseController;
use Modules\Reporting\Models\ReportRepository;

class ReportController extends BaseController
{
    private ReportRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new ReportRepository();
    }

    public function index(): string
    {
        return $this->render('Reporting', 'index', [
            'title' => 'Executive Dashboard',
            'summary' => $this->repo->getSummaryStats(),
            'revenue' => $this->repo->getMonthlyRevenue(),
            'services' => $this->repo->getServiceDistribution()
        ]);
    }
}
