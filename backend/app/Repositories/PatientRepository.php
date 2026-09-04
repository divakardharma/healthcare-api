<?php

class PatientRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO patients
                (user_id, patient_name, email, mobile, date_of_birth, gender, address, medical_data)
                VALUES
                (:user_id, :patient_name, :email, :mobile, :date_of_birth, :gender, :address, :medical_data)";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':user_id'       => $data['user_id'] ?? null,
            ':patient_name'  => $data['patient_name'],
            ':email'         => $data['email'] ?? null,
            ':mobile'        => $data['mobile'],
            ':date_of_birth' => $data['date_of_birth'] ?? null,
            ':gender'        => $data['gender'] ?? null,
            ':address'       => $data['address'] ?? null,
            ':medical_data'  => $data['medical_data'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'patient_name',
            'email',
            'mobile',
            'date_of_birth',
            'gender',
            'address',
            'medical_data'
        ];

        $fields = [];
        $params = [':id' => $id];

        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $fields[] = "{$column} = :{$column}";
                $params[":{$column}"] = $data[$column];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE patients SET "
             . implode(', ', $fields)
             . " WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE patients
             SET deleted_at = NOW()
             WHERE id = :id
             AND deleted_at IS NULL"
        );

        return $stmt->execute([
            ':id' => $id
        ]);
    }

    public function findById(int $id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM patients
             WHERE id = :id
             AND deleted_at IS NULL"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findAll(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM patients
             WHERE deleted_at IS NULL
             ORDER BY id DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByUserId(int $userId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM patients
             WHERE user_id = :user_id
             AND deleted_at IS NULL
             LIMIT 1"
        );

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findAppointmentsForPatient(int $patientId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM appointments
             WHERE patient_id = :patient_id
             ORDER BY appointment_date DESC,
                      appointment_time DESC"
        );

        $stmt->execute([
            ':patient_id' => $patientId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}