<?php

require_once __DIR__ . '/../Services/PrescriptionService.php';


class PrescriptionController
{
    private PrescriptionService $prescriptionService;


    public function __construct()
    {
        $this->prescriptionService = new PrescriptionService();
    }


    // ========================================
    // Create Prescription
    // ========================================

    public function create(array $user): array
    {
        // Get JSON request body
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );


        // Get tenant ID
        $tenantId = $user['tenant_id'];


        // Get request data
        $patientId = $input['patient_id'] ?? 0;

        $providerId = $input['provider_id'] ?? 0;

        $appointmentId = $input['appointment_id'] ?? null;

        $notes = $input['notes'] ?? '';

        $items = $input['items'] ?? [];


        // Create prescription
        $prescriptionId =
            $this->prescriptionService->createPrescription(
                (int) $tenantId,
                (int) $patientId,
                (int) $providerId,
                $appointmentId ? (int) $appointmentId : null,
                $notes,
                $items
            );


        return [
            'message' => 'Prescription created successfully',
            'prescription_id' => $prescriptionId
        ];
    }

    // ========================================
// Get All Prescriptions
// ========================================

public function getAll(array $user): array
{
    $tenantId = $user['tenant_id'];

    $prescriptions =
        $this->prescriptionService
            ->getAllPrescriptions(
                (int) $tenantId
            );

    return [
        'message' => 'Prescriptions fetched successfully',
        'data' => $prescriptions
    ];
}


// ========================================
// Get Prescription By ID
// ========================================

public function getById(
    int $prescriptionId,
    array $user
): array {

    $tenantId = $user['tenant_id'];

    $prescription =
        $this->prescriptionService
            ->getPrescriptionById(
                $prescriptionId,
                (int) $tenantId
            );

    return [
        'message' => 'Prescription fetched successfully',
        'data' => $prescription
    ];
}

// ========================================
// Update Prescription
// ========================================

public function update(
    int $prescriptionId,
    array $user
): array {

    // Get JSON request body
    $input = json_decode(
        file_get_contents('php://input'),
        true
    );


    // Get tenant ID
    $tenantId = $user['tenant_id'];


    // Get request data
    $patientId = $input['patient_id'] ?? 0;

    $providerId = $input['provider_id'] ?? 0;

    $appointmentId =
        $input['appointment_id'] ?? null;

    $notes = $input['notes'] ?? '';

    $status = $input['status'] ?? 'Pending';

    $items = $input['items'] ?? [];


    // Update prescription
    $this->prescriptionService->updatePrescription(
        $prescriptionId,
        (int) $tenantId,
        (int) $patientId,
        (int) $providerId,
        $appointmentId ? (int) $appointmentId : null,
        $notes,
        $status,
        $items
    );


    return [
        'message' => 'Prescription updated successfully'
    ];
}

// ========================================
// Delete Prescription
// ========================================

public function delete(
    int $prescriptionId,
    array $user
): array {

    $tenantId = (int) $user['tenant_id'];

    $this->prescriptionService->deletePrescription(
        $prescriptionId,
        $tenantId
    );

    return [
        'message' => 'Prescription deleted successfully'
    ];
}

// ========================================
// Update Prescription Status
// ========================================

public function updateStatus(
    int $prescriptionId,
    array $user
): array {

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    $status = $input['status'] ?? '';

    $tenantId = $user['tenant_id'];

    $this->prescriptionService->updateStatus(
        $prescriptionId,
        $tenantId,
        $status
    );

    return [
        'message' => 'Prescription status updated successfully'
    ];
}
}