<?php

require_once __DIR__ . '/../Repositories/PatientRepository.php';
require_once __DIR__ . '/../Security/AES.php';

class PatientService
{
    // Number of patients returned per API request. The frontend shows 8 per
    // screen, so one batch covers two UI pages. Owned by the backend only.
    public const BATCH_SIZE = 16;

    // Safety cap so a huge ?page= value can never overflow the OFFSET.
    private const MAX_PAGE = 1000000;

    private PatientRepository $patientRepository;
    private string $aesKey;

    public function __construct(PatientRepository $patientRepository)
    {
        $this->patientRepository = $patientRepository;
        $this->aesKey = $_ENV['AES_KEY'] ?? '';
    }

   public function createPatient(array $data, int $tenantId): array
{
    if (empty($data['patient_name'])) {
        throw new Exception('Patient name is required');
    }

    $mobile = preg_replace('/\D/', '', trim($data['mobile'] ?? ''));

    if ($mobile === '') {
        throw new Exception('Mobile number is required');
    }

    if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
        throw new Exception(
            'Mobile number must be a valid 10-digit Indian mobile number'
        );
    }

    $data['mobile'] = $mobile;

    if (
        !empty($data['email']) &&
        !filter_var($data['email'], FILTER_VALIDATE_EMAIL)
    ) {
        throw new Exception('Invalid email format');
    }

    if (!empty($data['date_of_birth'])) {

        $date = DateTime::createFromFormat(
            'Y-m-d',
            $data['date_of_birth']
        );

        if (
            !$date ||
            $date->format('Y-m-d') !== $data['date_of_birth']
        ) {
            throw new Exception('Invalid date of birth');
        }

        if ($date > new DateTime()) {
            throw new Exception(
                'Date of birth cannot be in the future'
            );
        }
    }

    $payload = [
        'user_id'       => $data['user_id'] ?? null,
        'patient_name'  => $data['patient_name'],
        'email'         => $data['email'] ?? null,
        'mobile'        => $data['mobile'],
        'date_of_birth' => $data['date_of_birth'] ?? null,
        'gender'        => $data['gender'] ?? null,
        'address'       => $data['address'] ?? null,
        'medical_data'  => $this->encryptMedicalData(
            $data['medical_data'] ?? null
        )
    ];

    $id = $this->patientRepository->create($payload);

    return $this->getPatient($id, $tenantId);
}

  public function updatePatient(
    int $id,
    int $tenantId,
    array $data
): array {

    $existing = $this->patientRepository->findById($id);

    if (!$existing) {
        throw new Exception('Patient not found');
    }

    $allowed = [
        'patient_name',
        'email',
        'mobile',
        'date_of_birth',
        'gender',
        'address',
        'medical_data'
    ];

    $updates = [];

    foreach ($allowed as $field) {

        if (array_key_exists($field, $data)) {

            $updates[$field] = $field === 'medical_data'
                ? $this->encryptMedicalData($data[$field])
                : $data[$field];
        }
    }

    if (
        !empty($updates['email']) &&
        !filter_var(
            $updates['email'],
            FILTER_VALIDATE_EMAIL
        )
    ) {
        throw new Exception('Invalid email format');
    }

    if (isset($updates['mobile'])) {

        $mobile = preg_replace(
            '/\D/',
            '',
            trim($updates['mobile'])
        );

        if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
            throw new Exception(
                'Mobile number must be a valid 10-digit Indian mobile number'
            );
        }

        $updates['mobile'] = $mobile;
    }

    if (!empty($updates['date_of_birth'])) {

        $date = DateTime::createFromFormat(
            'Y-m-d',
            $updates['date_of_birth']
        );

        if (
            !$date ||
            $date->format('Y-m-d') !== $updates['date_of_birth']
        ) {
            throw new Exception('Invalid date of birth');
        }

        if ($date > new DateTime()) {
            throw new Exception(
                'Date of birth cannot be in the future'
            );
        }
    }

    if (!empty($updates)) {
        $this->patientRepository->update(
            $id,
            $updates
        );
    }

    return $this->getPatient($id, $tenantId);
}

    public function deletePatient(
        int $id,
        int $tenantId
    ): bool {

        $existing = $this->patientRepository->findById($id);

        if (!$existing) {
            throw new Exception('Patient not found');
        }

        return $this->patientRepository->softDelete($id);
    }

    public function getPatient(
        int $id,
        int $tenantId
    ): array {

        $patient = $this->patientRepository->findById($id);

        if (!$patient) {
            throw new Exception('Patient not found');
        }

        $patient['medical_data'] =
            $this->decryptMedicalData(
                $patient['medical_data']
            );

        return $patient;
    }

    public function getPatientWithAppointments(
        int $id,
        int $tenantId
    ): array {

        $patient = $this->getPatient(
            $id,
            $tenantId
        );

        $patient['appointments'] =
            $this->patientRepository->findAppointmentsForPatient(
                $id
            );

        return $patient;
    }

    public function getAllPatients(
        int $tenantId
    ): array {

        $patients = $this->patientRepository->findAll();

        foreach ($patients as &$patient) {

            $patient['medical_data'] =
                $this->decryptMedicalData(
                    $patient['medical_data']
                );
        }

        unset($patient);

        return $patients;
    }
    public function getPatientsPage(
        int $page,
        int $tenantId
    ): array {

        $page   = min(max(1, $page), self::MAX_PAGE);
        $limit  = self::BATCH_SIZE;
        $offset = ($page - 1) * $limit;

        $patients = $this->patientRepository->findPaginated(
            $limit,
            $offset
        );

        foreach ($patients as &$patient) {

            $patient['medical_data'] =
                $this->decryptMedicalData(
                    $patient['medical_data']
                );
        }

        unset($patient);

        $total = $this->patientRepository->countActive();

        return [
            'patients'   => $patients,
            'pagination' => [
                'page'     => $page,
                'limit'    => $limit,
                'total'    => $total,
                'has_more' => ($page * $limit) < $total,
            ],
        ];
    }

private function encryptMedicalData(
    ?string $data
): ?string {

    if ($data === null || $data === '') {
        return null;
    }

    if (empty($this->aesKey)) {
        throw new Exception('Encryption key is not configured');
    }

    return AES::encrypt(
        $data,
        $this->aesKey
    );
}

   private function decryptMedicalData(
    ?string $data
): ?string {

    if ($data === null || $data === '') {
        return $data;
    }

    if (empty($this->aesKey)) {
        throw new Exception('Encryption key is not configured');
    }

    $decrypted = AES::decrypt(
        $data,
        $this->aesKey
    );

    if ($decrypted === false) {
        throw new Exception('Unable to decrypt medical data');
    }

    return $decrypted;
}
}