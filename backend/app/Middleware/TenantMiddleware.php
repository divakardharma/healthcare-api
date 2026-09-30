<?php

require_once __DIR__ . '/../Helpers/Response.php';

class TenantMiddleware
{
    public static function validate(int $urlTenantId, int $jwtTenantId): void
    {
        if ($urlTenantId !== $jwtTenantId) {
            Response::error(
                'Tenant access denied',
                403
            );
        }
    }
}