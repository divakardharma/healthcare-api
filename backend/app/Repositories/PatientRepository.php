<?php

require_once __DIR__ . '/../Security/AES.php';

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
            ':patient_name'  => AES::encryptField($data['patient_name']),
            ':email'         => AES::encryptField($data['email'] ?? null),
            ':mobile'        => AES::encryptField($data['mobile'] ?? null),
            ':date_of_birth' => AES::encryptField($data['date_of_birth'] ?? null),
            ':gender'        => AES::encryptField($data['gender'] ?? null),
            ':address'       => AES::encryptField($data['address'] ?? null),
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

                $params[":{$column}"] =
                    in_array($column, ['patient_name', 'email', 'mobile', 'date_of_birth', 'gender', 'address'], true)
                        ? AES::encryptField($data[$column])
                        : $data[$column];
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

        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        return $patient === false ? false : $this->decryptRow($patient);
    }

    public function findAll(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM patients
             WHERE deleted_at IS NULL
             ORDER BY id DESC"
        );

        return array_map(
            [$this, 'decryptRow'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
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

        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        return $patient === false ? false : $this->decryptRow($patient);
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

    return array_map(
        [$this, 'decryptAppointmentRow'],
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );
}

private function decryptAppointmentRow(array $appointment): array
{
    if (isset($appointment['reason'])) {
        $appointment['reason'] = AES::decryptField($appointment['reason']);
    }

    return $appointment;
}

    /*
    |--------------------------------------------------------------------------
    | DECRYPT ROW
    |--------------------------------------------------------------------------
    | patient_name, email, mobile, date_of_birth, gender, address are stored 
    | AES encrypted (see create()/update()). Checks if data is encrypted before 
    | decrypting to support old plaintext records (backward compatible).
    |--------------------------------------------------------------------------
    */
    private function decryptRow(array $patient): array
    {
        if (isset($patient['patient_name']) && $this->isEncrypted($patient['patient_name'])) {
            $patient['patient_name'] = AES::decryptField($patient['patient_name']);
        }

        if (isset($patient['email']) && $this->isEncrypted($patient['email'])) {
            $patient['email'] = AES::decryptField($patient['email']);
        }

        if (isset($patient['mobile']) && $this->isEncrypted($patient['mobile'])) {
            $patient['mobile'] = AES::decryptField($patient['mobile']);
        }

        if (isset($patient['date_of_birth']) && $this->isEncrypted($patient['date_of_birth'])) {
            $patient['date_of_birth'] = AES::decryptField($patient['date_of_birth']);
        }

        if (isset($patient['gender']) && $this->isEncrypted($patient['gender'])) {
            $patient['gender'] = AES::decryptField($patient['gender']);
        }

        if (isset($patient['address']) && $this->isEncrypted($patient['address'])) {
            $patient['address'] = AES::decryptField($patient['address']);
        }

        return $patient;
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK IF ENCRYPTED
    |--------------------------------------------------------------------------
    | Encrypted data is base64 with '=' padding. Plaintext is not.
    | Returns true if value looks encrypted, false if plaintext.
    |--------------------------------------------------------------------------
    */
    private function isEncrypted(string $value): bool
{
    return !empty($value) && (strpos($value, '=') !== false || strpos($value, '/') !== false || strpos($value, '+') !== false);
}
}