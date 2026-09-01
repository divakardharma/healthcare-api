<?php

require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Helpers/Response.php';

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function register(array $data): void
    {
        if (
            empty($data['name']) ||
            empty($data['email']) ||
            empty($data['password']) ||
            empty($data['tenant_id'])
        ) {
            Response::error(
                'Name, email, password and tenant_id are required',
                422
            );
        }

        try {
            $userId = $this->authService->register(
                $data['name'],
                $data['email'],
                $data['password'],
                (int) $data['tenant_id']
            );

            Response::success(
                ['user_id' => $userId],
                'User registered successfully',
                201
            );

        } catch (Exception $e) {
            Response::error(
                $e->getMessage(),
                409
            );
        }
    }

    public function login(array $data, string $jwtSecret): void
    {
        if (
            empty($data['email']) ||
            empty($data['password'])
        ) {
            Response::error(
                'Email and password are required',
                422
            );
        }

        try {
            $result = $this->authService->login(
                $data['email'],
                $data['password'],
                $jwtSecret
            );

            Response::success(
                $result,
                'Login successful'
            );

        } catch (Exception $e) {
            Response::error(
                $e->getMessage(),
                401
            );
        }
    }


    public function changePassword(
    array $data,
    int $userId
): void {
    if (
        empty($data['current_password']) ||
        empty($data['new_password'])
    ) {
        Response::error(
            'Current password and new password are required',
            422
        );
    }

    try {
        $this->authService->changePassword(
            $userId,
            $data['current_password'],
            $data['new_password']
        );

        Response::success(
            null,
            'Password changed successfully'
        );

    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            400
        );
    }
}


public function logout(int $userId): void
{
    try {
        $this->authService->logout($userId);

        Response::success(
            null,
            'Logout successful'
        );

    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            500
        );
    }
}
}