<?php

require_once __DIR__ . '/../Config/database.php';

class PrescriptionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }


    // ========================================
    // Create Prescription with Items
    // ========================================

    public function createPrescription(
        int $tenantId,
        int $patientId,
        int $providerId,
        ?int $appointmentId,
        string $notes,
        array $items
    ): int {

        try {

            // Start database transaction
            $this->pdo->beginTransaction();


            // ----------------------------------------
            // Insert into prescriptions table
            // ----------------------------------------

            $stmt = $this->pdo->prepare(
                "INSERT INTO prescriptions
                (
                    tenant_id,
                    patient_id,
                    provider_id,
                    appointment_id,
                    notes,
                    status
                )
                VALUES (?, ?, ?, ?, ?, 'Pending')"
            );

            $stmt->execute([
                $tenantId,
                $patientId,
                $providerId,
                $appointmentId,
                $notes
            ]);


            // Get newly created prescription ID
            $prescriptionId = (int) $this->pdo->lastInsertId();


            // ----------------------------------------
            // Insert Prescription Items
            // ----------------------------------------

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


            // Loop through all medicines
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


            // Everything successful → save permanently
            $this->pdo->commit();

            return $prescriptionId;

        } catch (Exception $e) {

            // Something failed → undo everything
            $this->pdo->rollBack();

            throw $e;
        }
    }

  
// Get All Prescriptions
// ========================================

public function getAll(int $tenantId): array
{
    $stmt = $this->pdo->prepare(
        "SELECT *
        FROM prescriptions
        WHERE tenant_id = ?
        ORDER BY id DESC"
    );

    $stmt->execute([$tenantId]);

    return $stmt->fetchAll();
}


// ========================================
// Get Prescription By ID
// ========================================

public function getById(
    int $prescriptionId,
    int $tenantId
): array|false {

    $stmt = $this->pdo->prepare(
        "SELECT *
        FROM prescriptions
        WHERE id = ?
        AND tenant_id = ?
        LIMIT 1"
    );

    $stmt->execute([
        $prescriptionId,
        $tenantId
    ]);

    return $stmt->fetch() ?: false;
}


// ========================================
// Get Prescription Items
// ========================================

// ========================================
// Get Prescription Items
// ========================================

public function getItems(
    int $prescriptionId
): array {

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
// Update Prescription with Items
// ========================================

public function updatePrescription(
    int $prescriptionId,
    int $tenantId,
    int $patientId,
    int $providerId,
    ?int $appointmentId,
    ?string $notes,
    string $status,
    array $items
): bool {

    try {

        // Start transaction
        $this->pdo->beginTransaction();


        // ----------------------------------------
        // Update Prescription
        // ----------------------------------------

        $stmt = $this->pdo->prepare(
            "UPDATE prescriptions
            SET
                patient_id = ?,
                provider_id = ?,
                appointment_id = ?,
                notes = ?,
                status = ?
            WHERE id = ?
            AND tenant_id = ?"
        );

        $stmt->execute([
            $patientId,
            $providerId,
            $appointmentId,
            $notes,
            $status,
            $prescriptionId,
            $tenantId
        ]);


        // Check prescription exists
        if ($stmt->rowCount() === 0) {

            $checkStmt = $this->pdo->prepare(
                "SELECT id
                FROM prescriptions
                WHERE id = ?
                AND tenant_id = ?"
            );

            $checkStmt->execute([
                $prescriptionId,
                $tenantId
            ]);

            if (!$checkStmt->fetch()) {
                throw new Exception('Prescription not found');
            }
        }


        // ----------------------------------------
        // Delete Old Prescription Items
        // ----------------------------------------

        $deleteStmt = $this->pdo->prepare(
            "DELETE FROM prescription_items
            WHERE prescription_id = ?"
        );

        $deleteStmt->execute([
            $prescriptionId
        ]);


        // ----------------------------------------
        // Insert New Prescription Items
        // ----------------------------------------

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


        // Save everything
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

public function deletePrescription(
    int $prescriptionId,
    int $tenantId
): bool {

    try {

        // Start transaction
        $this->pdo->beginTransaction();


        // ----------------------------------------
        // Delete prescription items first
        // ----------------------------------------

        $itemStmt = $this->pdo->prepare(
            "DELETE FROM prescription_items
             WHERE prescription_id = ?"
        );

        $itemStmt->execute([
            $prescriptionId
        ]);


        // ----------------------------------------
        // Delete prescription
        // ----------------------------------------

        $prescriptionStmt = $this->pdo->prepare(
            "DELETE FROM prescriptions
             WHERE id = ?
             AND tenant_id = ?"
        );

        $prescriptionStmt->execute([
            $prescriptionId,
            $tenantId
        ]);


        // Check whether prescription existed
        if ($prescriptionStmt->rowCount() === 0) {
            throw new Exception('Prescription not found');
        }


        // Save changes
        $this->pdo->commit();

        return true;

    } catch (Exception $e) {

        // Undo everything if something fails
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
    int $tenantId,
    string $status
): bool {

    $stmt = $this->pdo->prepare(
        "UPDATE prescriptions
        SET status = ?
        WHERE id = ?
        AND tenant_id = ?"
    );

    return $stmt->execute([
        $status,
        $prescriptionId,
        $tenantId
    ]);
}
}