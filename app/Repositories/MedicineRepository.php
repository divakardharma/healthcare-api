<?php

require_once __DIR__ . '/../Config/database.php';

class MedicineRepository
{
    private PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }


    // ----------------------------------------
    // CREATE MEDICINE
    // ----------------------------------------

    public function create(
        int $tenantId,
        string $name,
        ?string $description,
        int $stockQuantity
    ): int {

        $stmt = $this->pdo->prepare(
            "INSERT INTO medicines
            (tenant_id, name, description, stock_quantity)
            VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $tenantId,
            $name,
            $description,
            $stockQuantity
        ]);

        return (int) $this->pdo->lastInsertId();
    }


    // ----------------------------------------
    // GET ALL MEDICINES
    // ----------------------------------------

    public function getAll(int $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM medicines
             WHERE tenant_id = ?
             ORDER BY id DESC"
        );

        $stmt->execute([$tenantId]);

        return $stmt->fetchAll();
    }


    // ----------------------------------------
    // GET SINGLE MEDICINE
    // ----------------------------------------

    public function getById(
        int $medicineId,
        int $tenantId
    ): array|false {

        $stmt = $this->pdo->prepare(
            "SELECT * FROM medicines
             WHERE id = ?
             AND tenant_id = ?
             LIMIT 1"
        );

        $stmt->execute([
            $medicineId,
            $tenantId
        ]);

        return $stmt->fetch();
    }


    // ----------------------------------------
    // UPDATE MEDICINE
    // ----------------------------------------

    public function update(
        int $medicineId,
        int $tenantId,
        string $name,
        ?string $description,
        int $stockQuantity
    ): bool {

        $stmt = $this->pdo->prepare(
            "UPDATE medicines
             SET name = ?,
                 description = ?,
                 stock_quantity = ?
             WHERE id = ?
             AND tenant_id = ?"
        );

        return $stmt->execute([
            $name,
            $description,
            $stockQuantity,
            $medicineId,
            $tenantId
        ]);
    }


    // ----------------------------------------
    // DELETE MEDICINE
    // ----------------------------------------

    public function delete(
        int $medicineId,
        int $tenantId
    ): bool {

        $stmt = $this->pdo->prepare(
            "DELETE FROM medicines
             WHERE id = ?
             AND tenant_id = ?"
        );

        return $stmt->execute([
            $medicineId,
            $tenantId
        ]);
    }
}