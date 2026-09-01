<?php

require_once __DIR__ . '/../Config/database.php';

class RefreshTokenRepository
{
    private mysqli $conn;

    public function __construct()
    {
        global $conn;

        $this->conn = $conn;
    }

    public function create(
        int $userId,
        string $tokenHash,
        string $expiresAt
    ): int {
        $stmt = $this->conn->prepare(
            "INSERT INTO refresh_tokens
            (user_id, token_hash, expires_at)
            VALUES (?, ?, ?)"
        );

        $stmt->bind_param(
            "iss",
            $userId,
            $tokenHash,
            $expiresAt
        );

        $stmt->execute();

        return $stmt->insert_id;
    }

    public function findByTokenHash(string $tokenHash): array|false
    {
        $stmt = $this->conn->prepare(
            "SELECT *
             FROM refresh_tokens
             WHERE token_hash = ?
             AND revoked = FALSE
             LIMIT 1"
        );

        $stmt->bind_param("s", $tokenHash);

        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    public function revoke(int $id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE refresh_tokens
             SET revoked = TRUE
             WHERE id = ?"
        );

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }


    public function revokeAllForUser(int $userId): bool
{
    $stmt = $this->conn->prepare(
        "UPDATE refresh_tokens
         SET revoked = TRUE
         WHERE user_id = ?
         AND revoked = FALSE"
    );

    $stmt->bind_param("i", $userId);

    return $stmt->execute();
}
}