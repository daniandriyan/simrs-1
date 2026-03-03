<?php

declare(strict_types=1);

namespace Modules\DashboardModule;

use App\Core\BaseController;
use App\Core\Database;
use Psr\Container\ContainerInterface;

/**
 * Dashboard Controller
 * 
 * Handles dashboard HTTP requests.
 * Uses DashboardService for business logic.
 */
class DashboardController extends BaseController
{
    /**
     * @var DashboardService Dashboard service
     */
    private DashboardService $service;

    /**
     * Constructor
     *
     * @param ContainerInterface $container DI container
     * @param Database $db Database instance
     * @param DashboardService $service Dashboard service
     */
    public function __construct(
        ContainerInterface $container,
        Database $db,
        DashboardService $service
    ) {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Dashboard index page
     *
     * @return string Rendered HTML
     */
    public function index(): string
    {
        $this->requireAuth();

        // Get data from service
        $stats = $this->service->getStatistics();
        $recentActivities = $this->service->getRecentActivities(5);
        $departmentStats = $this->service->getDepartmentStats();

        return $this->view('dashboard/index.html', [
            'stats' => $stats,
            'recent_activities' => $recentActivities,
            'department_stats' => $departmentStats,
            'page_title' => 'Dashboard',
            'page_subtitle' => 'Selamat datang di SIMRS Modern',
        ]);
    }

    /**
     * API endpoint for statistics
     *
     * @return string JSON response
     */
    public function apiStats(): string
    {
        $this->requireAuth();

        $stats = $this->service->getStatistics();
        return $this->success($stats, 'Statistics retrieved successfully');
    }

    /**
     * API endpoint for recent activities
     *
     * @return string JSON response
     */
    public function apiActivities(): string
    {
        $this->requireAuth();

        $activities = $this->service->getRecentActivities(10);
        return $this->success($activities, 'Activities retrieved successfully');
    }

    /**
     * API endpoint for department statistics
     *
     * @return string JSON response
     */
    public function apiDepartments(): string
    {
        $this->requireAuth();

        $departments = $this->service->getDepartmentStats();
        return $this->success($departments, 'Department stats retrieved successfully');
    }
}
