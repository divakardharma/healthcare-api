<?php

require_once __DIR__ . '/../Security/AES.php';

class AppointmentRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE APPOINTMENT
    |--------------------------------------------------------------------------
    */
    public function create(array $data): int
    {
        $sql = "INSERT INTO appointments
                (
                    patient_id,
                    provider_id,
                    appointment_date,
                    appointment_time,
                    reason,
                    status
                )
                VALUES
                (
                    :patient_id,
                    :provider_id,
                    :appointment_date,
                    :appointment_time,
                    :reason,
                    :status
                )";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':patient_id'       => $data['patient_id'],
            ':provider_id'      => $data['provider_id'],
            ':appointment_date' => $data['appointment_date'],
            ':appointment_time' => $data['appointment_time'],
            ':reason'           => isset($data['reason']) ? AES::encryptField($data['reason']) : null,
            ':status'           => 'Scheduled'
        ]);

        return (int) $this->db->lastInsertId();
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE APPOINTMENT
    |--------------------------------------------------------------------------
    */
    public function update(
        int $id,
        array $data
    ): bool {

        $allowed = [
            'patient_id',
            'provider_id',
            'appointment_date',
            'appointment_time',
            'reason',
            'status'
        ];

        $fields = [];
        $params = [
            ':id' => $id
        ];

        foreach ($allowed as $column) {

            if (array_key_exists($column, $data)) {

                $fields[] = "{$column} = :{$column}";

                $params[":{$column}"] =
                    $column === 'reason'
                        ? AES::encryptField($data[$column])
                        : $data[$column];
            }
        }

        // Nothing to update
        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE appointments
                SET " . implode(', ', $fields) . "
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE STATUS
    |--------------------------------------------------------------------------
    */
    public function updateStatus(
        int $id,
        string $status
    ): bool {

        $stmt = $this->db->prepare(
            "UPDATE appointments
             SET status = :status
             WHERE id = :id"
        );

        return $stmt->execute([
            ':status' => $status,
            ':id'     => $id
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | FIND APPOINTMENT BY ID
    |--------------------------------------------------------------------------
    */
    public function findById(
        int $id
    ): array|false {

        $stmt = $this->db->prepare(
            "SELECT
                a.*,
                p.patient_name,
                u.name AS provider_name
             FROM appointments a
             LEFT JOIN patients p
                ON p.id = a.patient_id
             LEFT JOIN users u
                ON u.id = a.provider_id
             WHERE a.id = :id"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $appointment === false
            ? false
            : $this->decryptRow($appointment);
    }


    /*
    |--------------------------------------------------------------------------
    | FIND ALL APPOINTMENTS
    |--------------------------------------------------------------------------
    */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            "SELECT
                a.*,
                p.patient_name,
                u.name AS provider_name
             FROM appointments a
             LEFT JOIN patients p
                ON p.id = a.patient_id
             LEFT JOIN users u
                ON u.id = a.provider_id
             ORDER BY
                a.appointment_date DESC,
                a.appointment_time DESC"
        );

        return array_map(
            [$this, 'decryptRow'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK APPOINTMENT CONFLICT
    |--------------------------------------------------------------------------
    */
    public function hasConflict(
        int $providerId,
        string $date,
        string $time,
        ?int $excludeId = null
    ): bool {

        $sql = "SELECT COUNT(*)
                FROM appointments
                WHERE provider_id = :provider_id
                AND appointment_date = :appointment_date
                AND appointment_time = :appointment_time
                AND status != 'Cancelled'";

        $params = [
            ':provider_id'      => $providerId,
            ':appointment_date' => $date,
            ':appointment_time' => $time
        ];

        // Used while updating an existing appointment
        if ($excludeId !== null) {

            $sql .= " AND id != :exclude_id";

            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | GET APPOINTMENTS BY DATE
    |--------------------------------------------------------------------------
    */
    public function getByDate(
        string $date
    ): array {

        $stmt = $this->db->prepare(
            "SELECT
                a.*,
                p.patient_name,
                u.name AS provider_name
             FROM appointments a
             LEFT JOIN patients p
                ON p.id = a.patient_id
             LEFT JOIN users u
                ON u.id = a.provider_id
             WHERE a.appointment_date = :appointment_date
             ORDER BY a.appointment_time ASC"
        );

        $stmt->execute([
            ':appointment_date' => $date
        ]);

        return array_map(
            [$this, 'decryptRow'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GET APPOINTMENTS BY DATE RANGE
    |--------------------------------------------------------------------------
    */
    public function getByDateRange(
        string $startDate,
        string $endDate
    ): array {

        $stmt = $this->db->prepare(
            "SELECT
                a.*,
                p.patient_name,
                u.name AS provider_name
             FROM appointments a
             LEFT JOIN patients p
                ON p.id = a.patient_id
             LEFT JOIN users u
                ON u.id = a.provider_id
             WHERE a.appointment_date
             BETWEEN :start_date AND :end_date
             ORDER BY
                a.appointment_date ASC,
                a.appointment_time ASC"
        );

        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date'   => $endDate
        ]);

        return array_map(
            [$this, 'decryptRow'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GET UPCOMING APPOINTMENTS
    |--------------------------------------------------------------------------
    */
    public function getUpcomingAppointments(): array
    {
        $stmt = $this->db->query(
            "SELECT
                a.*,
                p.patient_name,
                u.name AS provider_name
             FROM appointments a
             LEFT JOIN patients p
                ON p.id = a.patient_id
             LEFT JOIN users u
                ON u.id = a.provider_id
             WHERE a.appointment_date >= CURDATE()
              AND a.status IN ('Scheduled', 'Confirmed')
             ORDER BY
                a.appointment_date ASC,
                a.appointment_time ASC"
        );

        return array_map(
            [$this, 'decryptRow'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DECRYPT ROW
    |--------------------------------------------------------------------------
    | patient_name and provider_name are pulled in via JOINs from the
    | patients / users tables, where they are stored AES encrypted
    | (see PatientRepository / UserRepository). Decrypt them here so
    | Appointment & Calendar API responses stay in plaintext, unchanged
    | from before this migration.
    |--------------------------------------------------------------------------
    */
    private function decryptRow(array $appointment): array
    {
        if (isset($appointment['patient_name'])) {
            $appointment['patient_name'] =
                AES::decryptField($appointment['patient_name']);
        }

        if (isset($appointment['provider_name'])) {
            $appointment['provider_name'] =
                AES::decryptField($appointment['provider_name']);
        }

        if (isset($appointment['reason'])) {
            $appointment['reason'] =
                AES::decryptField($appointment['reason']);
        }

        return $appointment;
    }
}