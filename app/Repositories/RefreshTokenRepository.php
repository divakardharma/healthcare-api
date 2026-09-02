<?php

require_once __DIR__ . '/../Config/database.php';

class RefreshTokenRepository
{
    private PDO $pdo;

    public function __construct(){
        global $pdo;
        $this->pdo = $pdo;
    }

    // --------------------------------------- CREATE -------------------------------------------

    public function create(int $userId,string $tokenHash,string $expiresAt): int {

        $stmt = $this->pdo->prepare( "INSERT INTO refresh_tokens  (user_id, token_hash, expires_at)  VALUES (?, ?, ?)" );

        $stmt->execute([  $userId,  $tokenHash,  $expiresAt ]);

        return (int) $this->pdo->lastInsertId();
    }

    // --------------------------------------- Find by Token ------------------------------------

    public function findByTokenHash(string $tokenHash): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM refresh_tokens WHERE token_hash = ? AND revoked = FALSE LIMIT 1");
        $stmt->execute([$tokenHash]);

        return $stmt->fetch() ?: false;
    }

    // --------------------------------------- REVOKE -------------------------------------------

    public function revoke(int $id): bool
    {
        $stmt = $this->pdo->prepare( "UPDATE refresh_tokens   SET revoked = TRUE   WHERE id = ?" );

        return $stmt->execute([$id]);
    }

    // --------------------------------------- Revoke All for User -------------------------------

    public function revokeAllForUser(int $userId): bool
    {
        $stmt = $this->pdo->prepare(  "UPDATE refresh_tokens  SET revoked = TRUE  WHERE user_id = ?  AND revoked = FALSE" );

        return $stmt->execute([$userId]);

    }
}