<?php

class RoleMiddleware
{
    /**
     * Ensures the authenticated user (payload decoded from the JWT by
     * AuthMiddleware) holds at least one of the allowed roles.
     *
     * @param array $payload      Decoded JWT payload, must contain user_id
     * @param array $allowedRoles e.g. ['Admin', 'Provider']
     */
    public static function handle(array $payload, array $allowedRoles): void
    {
        if (empty($payload['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'status' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }

        $roles = self::getUserRoles((int) $payload['user_id']);

        if (empty(array_intersect($allowedRoles, $roles))) {
            http_response_code(403);
            echo json_encode([
                'status' => false,
                'message' => 'You do not have permission to perform this action'
            ]);
            exit;
        }
    }

    public static function getUserRoles(int $userId): array
    {
        global $pdo;

        $stmt = $pdo->prepare(
            "SELECT r.name
             FROM roles r
             INNER JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = :user_id"
        );

        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}