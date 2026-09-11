<?php
require_once __DIR__ . '/../Config/database.php';
class PrescriptionRepository
{
    private PDO $pdo;

public function __construct(PDO $pdo)
{
    $this->pdo = $pdo;
}
    // ========================================
    // Create Prescription with Items
    // ========================================
    public function createPrescription(
        int $patientId,
        int $providerId,
        ?int $appointmentId,
        ?string $notes,
        array $items
    ): int {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                "INSERT INTO prescriptions
                (
                    patient_id,
                    provider_id,
                    appointment_id,
                    notes,
                    status
                )
                VALUES (?, ?, ?, ?, 'Pending')"
            );
            $stmt->execute([
                $patientId,
                $providerId,
                $appointmentId,
                $notes
            ]);
            $prescriptionId = (int)$this->pdo->lastInsertId();
            $itemStmt = $this->pdo->prepare(
                "INSERT INTO prescription_items
                (
                    prescription_id,
                    medicine_id,
                    dosage,
                    frequency,
                    duration,
                    quantity
                )
                VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($items as $item) {
                $itemStmt->execute([
                    $prescriptionId,
                    $item['medicine_id'],
                    $item['dosage'],
                    $item['frequency'],
                    $item['duration'],
                    $item['quantity']
                ]);
            }
            $this->pdo->commit();
            return $prescriptionId;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
    // ========================================
    // Get All Prescriptions
    // ========================================
    public function getAll(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM prescriptions ORDER BY id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
    // ========================================
    // Get Prescription By ID
    // ========================================
    public function getById(int $prescriptionId): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM prescriptions WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$prescriptionId]);
        return $stmt->fetch() ?: false;
    }
    // ========================================
    // Get Prescription Items
    // ========================================
    public function getItems(int $prescriptionId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                prescription_items.*,
                medicines.name AS medicine_name
            FROM prescription_items
            LEFT JOIN medicines
                ON medicines.id = prescription_items.medicine_id
            WHERE prescription_items.prescription_id = ?"
        );
        $stmt->execute([$prescriptionId]);
        return $stmt->fetchAll();
    }
    // ========================================
    // Update Prescription
    // ========================================
    public function updatePrescription(
        int $prescriptionId,
        int $patientId,
        int $providerId,
        ?int $appointmentId,
        ?string $notes,
        string $status,
        array $items
    ): bool {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                "UPDATE prescriptions
                SET patient_id = ?,
                    provider_id = ?,
                    appointment_id = ?,
                    notes = ?,
                    status = ?
                WHERE id = ?"
            );
            $stmt->execute([
                $patientId,
                $providerId,
                $appointmentId,
                $notes,
                $status,
                $prescriptionId
            ]);
            $checkStmt = $this->pdo->prepare(
                "SELECT id FROM prescriptions WHERE id = ?"
            );
            $checkStmt->execute([$prescriptionId]);
            if (!$checkStmt->fetch()) {
                throw new Exception('Prescription not found');
            }
            $deleteStmt = $this->pdo->prepare(
                "DELETE FROM prescription_items WHERE prescription_id = ?"
            );
            $deleteStmt->execute([$prescriptionId]);
            $itemStmt = $this->pdo->prepare(
                "INSERT INTO prescription_items
                (
                    prescription_id,
                    medicine_id,
                    dosage,
                    frequency,
                    duration,
                    quantity
                )
                VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($items as $item) {
                $itemStmt->execute([
                    $prescriptionId,
                    $item['medicine_id'],
                    $item['dosage'],
                    $item['frequency'],
                    $item['duration'],
                    $item['quantity']
                ]);
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
    // ========================================
    // Delete Prescription
    // ========================================
    public function deletePrescription(int $prescriptionId): bool
    {
        try {
            $this->pdo->beginTransaction();
            $itemStmt = $this->pdo->prepare(
                "DELETE FROM prescription_items WHERE prescription_id = ?"
            );
            $itemStmt->execute([$prescriptionId]);
            $prescriptionStmt = $this->pdo->prepare(
                "DELETE FROM prescriptions WHERE id = ?"
            );
            $prescriptionStmt->execute([$prescriptionId]);
            if ($prescriptionStmt->rowCount() === 0) {
                throw new Exception('Prescription not found');
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
    // ========================================
    // Update Prescription Status
    // ========================================
    public function updateStatus(
        int $prescriptionId,
        string $status
    ): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE prescriptions SET status = ? WHERE id = ?"
        );
        return $stmt->execute([
            $status,
            $prescriptionId
        ]);
    }
}