<?php
require_once __DIR__ . '/../Services/DashboardService.php';

class DashboardController
{
    private DashboardService $dashboardService;
    public function __construct(PDO $pdo)
    {
        $this->dashboardService = new DashboardService($pdo);
    }
    public function getDashboard(): array
    {
        $data = $this->dashboardService->getDashboardData();
        return [
            'message' => 'Dashboard data fetched successfully',
            'data' => $data
        ];
    }
}