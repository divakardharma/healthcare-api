<?php
require_once __DIR__ . '/../Repositories/DashboardRepository.php';

class DashboardService
{
    private DashboardRepository $dashboardRepository;
    public function __construct(PDO $pdo)
    {
        $this->dashboardRepository = new DashboardRepository($pdo);
    }
    public function getDashboardData(): array
    {
        $totalPatients = $this->dashboardRepository->getTotalPatients();
        $appointmentStatistics = $this->dashboardRepository->getAppointmentStatistics();
        $prescriptionSummary = $this->dashboardRepository->getPrescriptionSummary();
        $recentAppointments = $this->dashboardRepository->getRecentAppointments();
        return [
            'total_patients' => $totalPatients,
            'appointment_statistics' => [
                'total' => (int)($appointmentStatistics['total'] ?? 0),
                'scheduled' => (int)($appointmentStatistics['scheduled'] ?? 0),
                'completed' => (int)($appointmentStatistics['completed'] ?? 0),
                'cancelled' => (int)($appointmentStatistics['cancelled'] ?? 0)
            ],
            'prescription_summary' => [
                'total' => (int)($prescriptionSummary['total'] ?? 0),
                'pending' => (int)($prescriptionSummary['pending'] ?? 0),
                'verified' => (int)($prescriptionSummary['verified'] ?? 0),
                'dispensed' => (int)($prescriptionSummary['dispensed'] ?? 0),
                'cancelled' => (int)($prescriptionSummary['cancelled'] ?? 0)
            ],
            'recent_appointments' => $recentAppointments
        ];
    }
}