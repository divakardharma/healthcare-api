<?php

class AppointmentRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO appointments
                (tenant_id, patient_id, provider_id, appointment_date, appointment_time, reason, status)
                VALUES
                (:tenant_id, :patient_id, :provider_id, :appointment_date, :appointment_time, :reason, :status)";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':tenant_id'        => $data['tenant_id'],
            ':patient_id'       => $data['patient_id'],
            ':provider_id'      => $data['provider_id'],
            ':appointment_date' => $data['appointment_date'],
            ':appointment_time' => $data['appointment_time'],
            ':reason'           => $data['reason'] ?? null,
            ':status'           => 'Scheduled'
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $tenantId, array $data): bool
    {
        $allowed = ['patient_id', 'provider_id', 'appointment_date', 'appointment_time', 'reason', 'status'];
        $fields = [];
        $params = [':id' => $id, ':tenant_id' => $tenantId];

        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $fields[] = "{$column} = :{$column}";
                $params[":{$column}"] = $data[$column];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE appointments SET " . implode(', ', $fields) .
               " WHERE id = :id AND tenant_id = :tenant_id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function updateStatus(int $id, int $tenantId, string $status): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE appointments SET status = :status WHERE id = :id AND tenant_id = :tenant_id"
        );

        return $stmt->execute([
            ':status' => $status,
            ':id' => $id,
            ':tenant_id' => $tenantId
        ]);
    }

    public function findByIdForTenant(int $id, int $tenantId)
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, p.patient_name, u.name AS provider_name
             FROM appointments a
             LEFT JOIN patients p ON p.id = a.patient_id
             LEFT JOIN users u ON u.id = a.provider_id
             WHERE a.id = :id AND a.tenant_id = :tenant_id"
        );

        $stmt->execute([
            ':id' => $id,
            ':tenant_id' => $tenantId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findAllByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, p.patient_name, u.name AS provider_name
             FROM appointments a
             LEFT JOIN patients p ON p.id = a.patient_id
             LEFT JOIN users u ON u.id = a.provider_id
             WHERE a.tenant_id = :tenant_id
             ORDER BY a.appointment_date DESC, a.appointment_time DESC"
        );

        $stmt->execute([':tenant_id' => $tenantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasConflict(int $providerId, string $date, string $time, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM appointments
                WHERE provider_id = :provider_id
                AND appointment_date = :appointment_date
                AND appointment_time = :appointment_time
                AND status != 'Cancelled'";

        $params = [
            ':provider_id'      => $providerId,
            ':appointment_date' => $date,
            ':appointment_time' => $time
        ];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function getByDate(int $tenantId, string $date): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, p.patient_name, u.name AS provider_name
             FROM appointments a
             LEFT JOIN patients p ON p.id = a.patient_id
             LEFT JOIN users u ON u.id = a.provider_id
             WHERE a.tenant_id = :tenant_id
             AND a.appointment_date = :appointment_date
             ORDER BY a.appointment_time ASC"
        );

        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':appointment_date' => $date
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDateRange(int $tenantId, string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, p.patient_name, u.name AS provider_name
             FROM appointments a
             LEFT JOIN patients p ON p.id = a.patient_id
             LEFT JOIN users u ON u.id = a.provider_id
             WHERE a.tenant_id = :tenant_id
             AND a.appointment_date BETWEEN :start_date AND :end_date
             ORDER BY a.appointment_date ASC, a.appointment_time ASC"
        );

        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUpcomingAppointments(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, p.patient_name, u.name AS provider_name
             FROM appointments a
             LEFT JOIN patients p ON p.id = a.patient_id
             LEFT JOIN users u ON u.id = a.provider_id
             WHERE a.tenant_id = :tenant_id
             AND a.appointment_date >= CURDATE()
             AND a.status != 'Cancelled'
             ORDER BY a.appointment_date ASC, a.appointment_time ASC"
        );

        $stmt->execute([':tenant_id' => $tenantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}