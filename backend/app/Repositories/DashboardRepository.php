<?php
class DashboardRepository
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function getTotalPatients(): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total FROM patients"
        );
        $stmt->execute();
        return (int)$stmt->fetch()['total'];
    }
    public function getAppointmentStatistics(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) AS scheduled,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled
            FROM appointments"
        );
        $stmt->execute();
        return $stmt->fetch();
    }
    public function getPrescriptionSummary(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'Verified' THEN 1 ELSE 0 END) AS verified,
                SUM(CASE WHEN status = 'Dispensed' THEN 1 ELSE 0 END) AS dispensed,
                SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled
            FROM prescriptions"
        );
        $stmt->execute();
        return $stmt->fetch();
    }
    public function getRecentAppointments(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM appointments
            ORDER BY id DESC
            LIMIT 5"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}