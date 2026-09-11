<?php

require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Helpers/Response.php';

class AuthController
{
    private AuthService $authService;

    public function __construct(PDO $pdo)
    {
        $this->authService = new AuthService($pdo);
    }


    /*
    |--------------------------------------------------------------------------
    | REGISTER
    |--------------------------------------------------------------------------
    */

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

            $userId =
                $this->authService->register(
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


    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */

    public function login(
        array $data,
        string $jwtSecret,
        int $tenantId
    ): void {

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

            $result =
                $this->authService->login(
                    $data['email'],
                    $data['password'],
                    $jwtSecret,
                    $tenantId
                );

            /*
            |--------------------------------------------------------------------------
            | Return CSRF Token
            |--------------------------------------------------------------------------
            */


                        setcookie('refresh_token', $result['refresh_token'], [
                'expires'  => time() + (int)($_ENV['JWT_REFRESH_EXPIRY'] ?? 604800),
                'path'     => '/',
                'secure'   => (bool)($_ENV['COOKIE_SECURE'] ?? true),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            unset($result['refresh_token']);

            $result['csrf_token'] =
                $_SESSION['csrf_token'] ?? null;

            Response::success(
                $result,
                'Login successful'
            );

            // $result['csrf_token'] =
            //     $_SESSION['csrf_token'] ?? null;

            // Response::success(
            //     $result,
            //     'Login successful'
            // );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                401
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CHANGE PASSWORD
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function logout(
        int $userId
    ): void {

        try {

                       $this->authService->logout(
                $userId
            );

            setcookie('refresh_token', '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => (bool)($_ENV['COOKIE_SECURE'] ?? true),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

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


    /*
    |--------------------------------------------------------------------------
    | REFRESH TOKEN
    |--------------------------------------------------------------------------
    */

    public function refresh(
        array $data,
        string $jwtSecret
    ): void {

        if (
            empty($data['refresh_token'])
        ) {
            Response::error(
                'Refresh token is required',
                422
            );
        }

        try {


                    $tokens =
                $this->authService->refresh(
                    $data['refresh_token'],
                    $jwtSecret
                );

            setcookie('refresh_token', $tokens['refresh_token'], [
                'expires'  => time() + (int)($_ENV['JWT_REFRESH_EXPIRY'] ?? 604800),
                'path'     => '/',
                'secure'   => (bool)($_ENV['COOKIE_SECURE'] ?? true),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            unset($tokens['refresh_token']);

            Response::success(
                $tokens,
                'Tokens refreshed successfully'
            );

            // $tokens =
            //     $this->authService->refresh(
            //         $data['refresh_token'],
            //         $jwtSecret
            //     );

            // Response::success(
            //     $tokens,
            //     'Tokens refreshed successfully'
            // );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                401
            );
        }
    }
}