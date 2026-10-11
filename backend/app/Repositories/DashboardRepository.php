
<?php

require_once __DIR__ . '/../Security/AES.php';

class DashboardRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getTotalPatients(): int
    {
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM patients"
        );

        return (int) $stmt->fetchColumn();
    }

    public function getAppointmentStatistics(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'Scheduled'), 0) AS scheduled,
                COALESCE(SUM(status = 'Completed'), 0) AS completed,
                COALESCE(SUM(status = 'Cancelled'), 0) AS cancelled
             FROM appointments"
        );

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getPrescriptionSummary(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'Pending'), 0) AS pending,
                COALESCE(SUM(status = 'Verified'), 0) AS verified,
                COALESCE(SUM(status = 'Dispensed'), 0) AS dispensed,
                COALESCE(SUM(status = 'Cancelled'), 0) AS cancelled
             FROM prescriptions"
        );

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getRecentAppointments(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                a.id,
                a.patient_id,
                a.provider_id,
                a.appointment_date,
                a.appointment_time,
                a.status,
                p.patient_name,
                u.name AS provider_name
             FROM appointments a
             LEFT JOIN patients p
                ON p.id = a.patient_id
             LEFT JOIN users u
                ON u.id = a.provider_id
             ORDER BY a.id DESC
             LIMIT 5"
        );

        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($appointments as &$appointment) {
            if (!empty($appointment['patient_name'])) {
                $appointment['patient_name'] =
                    AES::decryptField($appointment['patient_name']);
            }

            if (!empty($appointment['provider_name'])) {
                $appointment['provider_name'] =
                    AES::decryptField($appointment['provider_name']);
            }
        }

        unset($appointment);

        return $appointments;
    }
}