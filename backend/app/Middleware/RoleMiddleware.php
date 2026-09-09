<?php

require_once __DIR__ . '/../Helpers/Response.php';

class RoleMiddleware
{
    /**
     * Ensures the authenticated user holds
     * at least one of the allowed roles.
     *
     * @param array $payload
     * @param array $allowedRoles
     * @param PDO   $tenantPdo Current tenant database connection
     */
    public static function handle(
        array $payload,
        array $allowedRoles,
        PDO $tenantPdo
    ): void {

        if (empty($payload['user_id'])) {

            Response::error(
                'Unauthorized',
                401
            );
        }

        $roles = self::getUserRoles(
            (int) $payload['user_id'],
            $tenantPdo
        );

        if (
            empty(
                array_intersect(
                    $allowedRoles,
                    $roles
                )
            )
        ) {

            Response::error(
                'You do not have permission to perform this action',
                403
            );
        }
    }

    public static function getUserRoles(
        int $userId,
        PDO $tenantPdo
    ): array {

        $stmt = $tenantPdo->prepare(
            "SELECT r.name
             FROM roles r
             INNER JOIN user_roles ur
                 ON ur.role_id = r.id
             WHERE ur.user_id = :user_id"
        );

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_COLUMN
        );
    }
}