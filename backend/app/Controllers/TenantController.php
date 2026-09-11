<?php

class TenantController
{
    private TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    public function register(array $data): void
    {
        try {

            $name = trim($data['name'] ?? '');
            $email = trim($data['email'] ?? '');
            $subdomain = strtolower(trim($data['subdomain'] ?? ''));
            $password = $data['password'] ?? '';

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            if ($name === '') {
                Response::error(
                    'Hospital name is required.',
                    400
                );
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Response::error(
                    'Valid email is required.',
                    400
                );
            }

            if ($subdomain === '') {
                Response::error(
                    'Subdomain is required.',
                    400
                );
            }

            if (!preg_match('/^[a-z0-9-]+$/', $subdomain)) {
                Response::error(
                    'Invalid subdomain. Only lowercase letters, numbers and hyphens are allowed.',
                    400
                );
            }

            if ($password === '') {
                Response::error(
                    'Password is required.',
                    400
                );
            }

            if (strlen($password) < 8) {
                Response::error(
                    'Password must be at least 8 characters.',
                    400
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Register Tenant
            |--------------------------------------------------------------------------
            */

            $tenant = $this->tenantService->registerTenant(
                $name,
                $email,
                $subdomain,
                $password
            );

            /*
            |--------------------------------------------------------------------------
            | Success Response
            |--------------------------------------------------------------------------
            */

            Response::success(
                $tenant,
                'Hospital registered successfully.',
                201
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                400
            );
        }
    }
}