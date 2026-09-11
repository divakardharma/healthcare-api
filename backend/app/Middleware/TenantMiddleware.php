<?php

class TenantMiddleware
{
    public static function validate(int $tenantId, int $userTenantId): void
    {
        if ($tenantId !== $userTenantId) {
            http_response_code(403);

            echo json_encode([
                'status' => false,
                'message' => 'Tenant access denied'
            ]);

            exit;
        }
    }
}