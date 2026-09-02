<?php

require_once __DIR__ . '/../Config/database.php';

class UserRepository
{
    private PDO $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    // ---------------------------------------- findByEmail ------------------------------------------

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);

        return $stmt->fetch() ?: false;
    }

    // ----------------------------------------- findById --------------------------------------------

    public function findById(int $userId): array|false
    {
        $stmt = $this->pdo->prepare( "SELECT * FROM users WHERE id = ? LIMIT 1" );
        $stmt->execute([$userId]);

        return $stmt->fetch() ?: false;
    }

    // --------------------------------------- Create ------------------------------------------------

    public function create( int $tenantId, string $name, string $email, string $password): int {

        $stmt = $this->pdo->prepare( "INSERT INTO users (tenant_id, name, email, password) VALUES (?, ?, ?, ?)" );
        $stmt->execute([ $tenantId, $name, $email, $password ]);

        return (int) $this->pdo->lastInsertId();
    }

    // ------------------------------------------- UpdatePassword ------------------------------------

    public function updatePassword( int $userId, string $hashedPassword ): bool {

        $stmt = $this->pdo->prepare(  "UPDATE users SET password = ? WHERE id = ?" );

        return $stmt->execute([
            $hashedPassword,
            $userId
        ]);
    }
}