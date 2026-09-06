<?php
require_once __DIR__ . '/../Repositories/PrescriptionRepository.php';
require_once __DIR__ . '/../Security/AES.php';

class PrescriptionService
{
    private PrescriptionRepository $prescriptionRepository;

   public function __construct(PDO $pdo)
{
    $this->prescriptionRepository = new PrescriptionRepository($pdo);
}

    // ========================================
    // Create Prescription
    // ========================================
    public function createPrescription(
        int $patientId,
        int $providerId,
        ?int $appointmentId,
        string $notes,
        array $items
    ): int {
        if ($patientId <= 0) {
            throw new Exception('Valid patient ID is required');
        }
        if ($providerId <= 0) {
            throw new Exception('Valid provider ID is required');
        }
        if (empty($items)) {
            throw new Exception('At least one medicine is required');
        }
        foreach ($items as $item) {
            if (empty($item['medicine_id'])) {
                throw new Exception('Medicine ID is required');
            }
            if (empty($item['dosage'])) {
                throw new Exception('Dosage is required');
            }
            if (empty($item['frequency'])) {
                throw new Exception('Frequency is required');
            }
            if (empty($item['duration'])) {
                throw new Exception('Duration is required');
            }
            if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                throw new Exception('Valid quantity is required');
            }
        }
        $aesKey = $_ENV['AES_KEY'] ?? '';
        if (empty($aesKey)) {
            throw new Exception('AES key is not configured');
        }
        $encryptedNotes = null;
        if (!empty($notes)) {
            $encryptedNotes = AES::encrypt($notes, $aesKey);
        }
        return $this->prescriptionRepository->createPrescription(
            $patientId,
            $providerId,
            $appointmentId,
            $encryptedNotes,
            $items
        );
    }

    // ========================================
    // Get All Prescriptions
    // ========================================
    public function getAllPrescriptions(): array
    {
        $prescriptions = $this->prescriptionRepository->getAll();
        $aesKey = $_ENV['AES_KEY'] ?? '';
        foreach ($prescriptions as &$prescription) {
            if (!empty($prescription['notes'])) {
                $decryptedNotes = AES::decrypt(
                    $prescription['notes'],
                    $aesKey
                );
                $prescription['notes'] = $decryptedNotes !== false
                    ? $decryptedNotes
                    : null;
            }
            $prescription['items'] = $this->prescriptionRepository->getItems(
                (int)$prescription['id']
            );
        }
        return $prescriptions;
    }

    // ========================================
    // Get Prescription By ID
    // ========================================
    public function getPrescriptionById(int $prescriptionId): array
    {
        if ($prescriptionId <= 0) {
            throw new Exception('Invalid prescription ID');
        }
        $prescription = $this->prescriptionRepository->getById(
            $prescriptionId
        );
        if (!$prescription) {
            throw new Exception('Prescription not found');
        }
        $aesKey = $_ENV['AES_KEY'] ?? '';
        if (!empty($prescription['notes'])) {
            $decryptedNotes = AES::decrypt(
                $prescription['notes'],
                $aesKey
            );
            $prescription['notes'] = $decryptedNotes !== false
                ? $decryptedNotes
                : null;
        }
        $prescription['items'] = $this->prescriptionRepository->getItems(
            (int)$prescription['id']
        );
        return $prescription;
    }

    // ========================================
    // Update Prescription
    // ========================================
    public function updatePrescription(
        int $prescriptionId,
        int $patientId,
        int $providerId,
        ?int $appointmentId,
        string $notes,
        string $status,
        array $items
    ): bool {
        if ($prescriptionId <= 0) {
            throw new Exception('Invalid prescription ID');
        }
        $existingPrescription = $this->prescriptionRepository->getById(
            $prescriptionId
        );
        if (!$existingPrescription) {
            throw new Exception('Prescription not found');
        }
        if ($patientId <= 0) {
            throw new Exception('Valid patient ID is required');
        }
        if ($providerId <= 0) {
            throw new Exception('Valid provider ID is required');
        }
        $allowedStatuses = [
            'Pending',
            'Verified',
            'Dispensed',
            'Cancelled'
        ];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new Exception('Invalid prescription status');
        }
        if (empty($items)) {
            throw new Exception('At least one medicine is required');
        }
        foreach ($items as $item) {
            if (empty($item['medicine_id'])) {
                throw new Exception('Medicine ID is required');
            }
            if (empty($item['dosage'])) {
                throw new Exception('Dosage is required');
            }
            if (empty($item['frequency'])) {
                throw new Exception('Frequency is required');
            }
            if (empty($item['duration'])) {
                throw new Exception('Duration is required');
            }
            if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                throw new Exception('Valid quantity is required');
            }
        }
        $aesKey = $_ENV['AES_KEY'] ?? '';
        if (empty($aesKey)) {
            throw new Exception('AES key is not configured');
        }
        $encryptedNotes = null;
        if (!empty($notes)) {
            $encryptedNotes = AES::encrypt($notes, $aesKey);
        }
        return $this->prescriptionRepository->updatePrescription(
            $prescriptionId,
            $patientId,
            $providerId,
            $appointmentId,
            $encryptedNotes,
            $status,
            $items
        );
    }

    // ========================================
    // Delete Prescription
    // ========================================
    public function deletePrescription(int $prescriptionId): bool
    {
        if ($prescriptionId <= 0) {
            throw new Exception('Invalid prescription ID');
        }
        $prescription = $this->prescriptionRepository->getById(
            $prescriptionId
        );
        if (!$prescription) {
            throw new Exception('Prescription not found');
        }
        return $this->prescriptionRepository->deletePrescription(
            $prescriptionId
        );
    }

    // ========================================
    // Update Prescription Status
    // ========================================
    public function updateStatus(
        int $prescriptionId,
        string $status
    ): void {
        if ($prescriptionId <= 0) {
            throw new Exception('Invalid prescription ID');
        }
        $allowedStatuses = [
            'Pending',
            'Verified',
            'Dispensed',
            'Cancelled'
        ];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new Exception('Invalid prescription status');
        }
        $prescription = $this->prescriptionRepository->getById(
            $prescriptionId
        );
        if (!$prescription) {
            throw new Exception('Prescription not found');
        }
        $this->prescriptionRepository->updateStatus(
            $prescriptionId,
            $status
        );
    }
}