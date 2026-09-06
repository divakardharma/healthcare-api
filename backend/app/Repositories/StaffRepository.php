<?php

class StaffRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }


    // ========================================
    // Create Staff
    // ========================================

    public function create(
        int $userId,
        int $roleId,
        string $status
    ): int {

        $stmt = $this->pdo->prepare(
            "INSERT INTO staff
            (
                user_id,
                role_id,
                status
            )
            VALUES (?, ?, ?)"
        );

        $stmt->execute([
            $userId,
            $roleId,
            $status
        ]);

        return (int) $this->pdo->lastInsertId();
    }


    // ========================================
    // Get All Staff
    // ========================================

    public function getAll(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                staff.id,
                staff.user_id,
                staff.role_id,
                staff.status,
                staff.created_at,
                staff.updated_at,
                roles.name AS role_name
            FROM staff
            LEFT JOIN roles
                ON roles.id = staff.role_id
            ORDER BY staff.id DESC"
        );

        $stmt->execute();

        return $stmt->fetchAll();
    }


    // ========================================
    // Get Staff By ID
    // ========================================

    public function getById(
        int $staffId
    ): array|false {

        $stmt = $this->pdo->prepare(
            "SELECT
                staff.id,
                staff.user_id,
                staff.role_id,
                staff.status,
                staff.created_at,
                staff.updated_at,
                roles.name AS role_name
            FROM staff
            LEFT JOIN roles
                ON roles.id = staff.role_id
            WHERE staff.id = ?
            LIMIT 1"
        );

        $stmt->execute([
            $staffId
        ]);

        return $stmt->fetch() ?: false;
    }


    // ========================================
    // Update Staff
    // ========================================

    public function update(
        int $staffId,
        int $userId,
        int $roleId,
        string $status
    ): bool {

        $stmt = $this->pdo->prepare(
            "UPDATE staff
            SET
                user_id = ?,
                role_id = ?,
                status = ?
            WHERE id = ?"
        );

        return $stmt->execute([
            $userId,
            $roleId,
            $status,
            $staffId
        ]);
    }


    // ========================================
    // Delete Staff
    // ========================================

    public function delete(
        int $staffId
    ): bool {

        $stmt = $this->pdo->prepare(
            "DELETE FROM staff
            WHERE id = ?"
        );

        return $stmt->execute([
            $staffId
        ]);
    }


    // ========================================
    // Update Staff Status
    // ========================================

    public function updateStatus(
        int $staffId,
        string $status
    ): bool {

        $stmt = $this->pdo->prepare(
            "UPDATE staff
            SET status = ?
            WHERE id = ?"
        );

        return $stmt->execute([
            $status,
            $staffId
        ]);
    }
}