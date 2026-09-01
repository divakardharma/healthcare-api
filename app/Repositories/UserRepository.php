<?php

require_once __DIR__ . '/../Config/database.php';

class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        global $pdo;

        $this->pdo = $pdo;
    }

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        return $stmt->fetch();
    }

    public function create(
        int $tenantId,
        string $name,
        string $email,
        string $password
    ): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users
            (tenant_id, name, email, password)
            VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $tenantId,
            $name,
            $email,
            $password
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}