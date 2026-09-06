<?php
require_once __DIR__ . '/../Services/PrescriptionService.php';

class PrescriptionController
{
    private PrescriptionService $prescriptionService;

public function __construct(PDO $pdo)
{
    $this->prescriptionService = new PrescriptionService($pdo);
}

    // ========================================
    // Create Prescription
    // ========================================
    public function create(): array
    {
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );
        if (!is_array($input)) {
            throw new Exception('Invalid JSON request');
        }
        $patientId = $input['patient_id'] ?? 0;
        $providerId = $input['provider_id'] ?? 0;
        $appointmentId = $input['appointment_id'] ?? null;
        $notes = $input['notes'] ?? '';
        $items = $input['items'] ?? [];
        $prescriptionId = $this->prescriptionService->createPrescription(
            (int)$patientId,
            (int)$providerId,
            $appointmentId ? (int)$appointmentId : null,
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
    public function getAll(): array
    {
        $prescriptions = $this->prescriptionService->getAllPrescriptions();
        return [
            'message' => 'Prescriptions fetched successfully',
            'data' => $prescriptions
        ];
    }

    // ========================================
    // Get Prescription By ID
    // ========================================
    public function getById(int $prescriptionId): array
    {
        $prescription = $this->prescriptionService->getPrescriptionById(
            $prescriptionId
        );
        return [
            'message' => 'Prescription fetched successfully',
            'data' => $prescription
        ];
    }

    // ========================================
    // Update Prescription
    // ========================================
    public function update(int $prescriptionId): array
    {
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );
        if (!is_array($input)) {
            throw new Exception('Invalid JSON request');
        }
        $patientId = $input['patient_id'] ?? 0;
        $providerId = $input['provider_id'] ?? 0;
        $appointmentId = $input['appointment_id'] ?? null;
        $notes = $input['notes'] ?? '';
        $status = $input['status'] ?? 'Pending';
        $items = $input['items'] ?? [];
        $this->prescriptionService->updatePrescription(
            $prescriptionId,
            (int)$patientId,
            (int)$providerId,
            $appointmentId ? (int)$appointmentId : null,
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
    public function delete(int $prescriptionId): array
    {
        $this->prescriptionService->deletePrescription(
            $prescriptionId
        );
        return [
            'message' => 'Prescription deleted successfully'
        ];
    }

    // ========================================
    // Update Prescription Status
    // ========================================
    public function updateStatus(int $prescriptionId): array
    {
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );
        if (!is_array($input)) {
            throw new Exception('Invalid JSON request');
        }
        $status = $input['status'] ?? '';
        $this->prescriptionService->updateStatus(
            $prescriptionId,
            $status
        );
        return [
            'message' => 'Prescription status updated successfully'
        ];
    }
}