<?php

require_once __DIR__ . '/../Repositories/PrescriptionRepository.php';
require_once __DIR__ . '/../Security/AES.php';


class PrescriptionService
{
    private PrescriptionRepository $prescriptionRepository;


    public function __construct()
    {
        $this->prescriptionRepository = new PrescriptionRepository();
    }


    // ========================================
    // Create Prescription
    // ========================================

    public function createPrescription(
        int $tenantId,
        int $patientId,
        int $providerId,
        ?int $appointmentId,
        string $notes,
        array $items
    ): int {

        // Validate patient
        if ($patientId <= 0) {
            throw new Exception('Valid patient ID is required');
        }


        // Validate provider
        if ($providerId <= 0) {
            throw new Exception('Valid provider ID is required');
        }


        // Validate prescription items
        if (empty($items)) {
            throw new Exception(
                'At least one medicine is required'
            );
        }


        // Validate every medicine item
        foreach ($items as $item) {

            if (empty($item['medicine_id'])) {
                throw new Exception(
                    'Medicine ID is required'
                );
            }

            if (empty($item['dosage'])) {
                throw new Exception(
                    'Dosage is required'
                );
            }

            if (empty($item['frequency'])) {
                throw new Exception(
                    'Frequency is required'
                );
            }

            if (empty($item['duration'])) {
                throw new Exception(
                    'Duration is required'
                );
            }

            if (
                !isset($item['quantity']) ||
                $item['quantity'] <= 0
            ) {
                throw new Exception(
                    'Valid quantity is required'
                );
            }
        }


        // ========================================
        // Encrypt sensitive prescription notes
        // ========================================

        $aesKey = $_ENV['AES_KEY'] ?? '';

        if (empty($aesKey)) {
            throw new Exception('AES key is not configured');
        }

        $encryptedNotes = null;

        if (!empty($notes)) {
            $encryptedNotes = AES::encrypt(
                $notes,
                $aesKey
            );
        }


        // Create prescription + items
        return $this->prescriptionRepository->createPrescription(
            $tenantId,
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

public function getAllPrescriptions(
    int $tenantId
): array {

    $prescriptions =
        $this->prescriptionRepository->getAll(
            $tenantId
        );

    $aesKey = $_ENV['AES_KEY'] ?? '';

    foreach ($prescriptions as &$prescription) {

        // Decrypt notes
        if (!empty($prescription['notes'])) {

            $decryptedNotes = AES::decrypt(
                $prescription['notes'],
                $aesKey
            );

            $prescription['notes'] =
                $decryptedNotes !== false
                    ? $decryptedNotes
                    : null;
        }

        // Get medicines for this prescription
        $prescription['items'] =
            $this->prescriptionRepository->getItems(
                (int) $prescription['id']
            );
    }

    return $prescriptions;
}

// ========================================
// Get Prescription By ID
// ========================================

public function getPrescriptionById(
    int $prescriptionId,
    int $tenantId
): array {

    $prescription =
        $this->prescriptionRepository->getById(
            $prescriptionId,
            $tenantId
        );

    if (!$prescription) {
        throw new Exception(
            'Prescription not found'
        );
    }

    $aesKey = $_ENV['AES_KEY'] ?? '';

    // Decrypt notes
    if (!empty($prescription['notes'])) {

        $decryptedNotes = AES::decrypt(
            $prescription['notes'],
            $aesKey
        );

        $prescription['notes'] =
            $decryptedNotes !== false
                ? $decryptedNotes
                : null;
    }

    // Get prescription medicines
    $prescription['items'] =
        $this->prescriptionRepository->getItems(
            (int) $prescription['id']
        );

    return $prescription;
}

// ========================================
// Update Prescription
// ========================================

public function updatePrescription(
    int $prescriptionId,
    int $tenantId,
    int $patientId,
    int $providerId,
    ?int $appointmentId,
    string $notes,
    string $status,
    array $items
): bool {

    // Check prescription exists
    $existingPrescription =
        $this->prescriptionRepository->getById(
            $prescriptionId,
            $tenantId
        );

    if (!$existingPrescription) {
        throw new Exception('Prescription not found');
    }


    // Validate patient
    if ($patientId <= 0) {
        throw new Exception('Valid patient ID is required');
    }


    // Validate provider
    if ($providerId <= 0) {
        throw new Exception('Valid provider ID is required');
    }


    // Validate status
    $allowedStatuses = [
        'Pending',
        'Approved',
        'Dispensed',
        'Cancelled'
    ];

    if (!in_array($status, $allowedStatuses)) {
        throw new Exception('Invalid prescription status');
    }


    // Validate items
    if (empty($items)) {
        throw new Exception(
            'At least one medicine is required'
        );
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

        if (
            !isset($item['quantity']) ||
            $item['quantity'] <= 0
        ) {
            throw new Exception(
                'Valid quantity is required'
            );
        }
    }


    // ----------------------------------------
    // Encrypt Notes
    // ----------------------------------------

    $aesKey = $_ENV['AES_KEY'] ?? '';

    if (empty($aesKey)) {
        throw new Exception('AES key is not configured');
    }

    $encryptedNotes = null;

    if (!empty($notes)) {
        $encryptedNotes = AES::encrypt(
            $notes,
            $aesKey
        );
    }


    // Update prescription
    return $this->prescriptionRepository->updatePrescription(
        $prescriptionId,
        $tenantId,
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

public function deletePrescription(
    int $prescriptionId,
    int $tenantId
): bool {

    if ($prescriptionId <= 0) {
        throw new Exception('Invalid prescription ID');
    }

    return $this->prescriptionRepository->deletePrescription(
        $prescriptionId,
        $tenantId
    );
}


// ========================================
// Update Prescription Status
// ========================================

public function updateStatus(
    int $prescriptionId,
    int $tenantId,
    string $status
): void {

    $allowedStatuses = [
        'Pending',
        'Verified',
        'Dispensed',
        'Cancelled'
    ];

    if (!in_array($status, $allowedStatuses)) {
        throw new Exception(
            'Invalid prescription status'
        );
    }

    $prescription =
        $this->prescriptionRepository->getById(
            $prescriptionId,
            $tenantId
        );

    if (!$prescription) {
        throw new Exception(
            'Prescription not found'
        );
    }

    $this->prescriptionRepository->updateStatus(
        $prescriptionId,
        $tenantId,
        $status
    );
}
}