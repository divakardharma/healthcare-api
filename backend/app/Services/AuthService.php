<?php

require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/RefreshTokenRepository.php';
require_once __DIR__ . '/../Security/Hash.php';
require_once __DIR__ . '/../Security/JWT.php';
require_once __DIR__ . '/../Security/CSRF.php';

class AuthService
{
    private UserRepository $userRepository;
    private RefreshTokenRepository $refreshTokenRepository;

    public function __construct(PDO $pdo)
    {
        $this->userRepository = new UserRepository($pdo);
        $this->refreshTokenRepository = new RefreshTokenRepository($pdo);
    }


    /*
    |--------------------------------------------------------------------------
    | REGISTER
    |--------------------------------------------------------------------------
    */
    public function register(
        string $name,
        string $email,
        string $password,
        int $tenantId
    ): int {

        $existingUser =
            $this->userRepository->findByEmail($email);

        if ($existingUser !== false) {
            throw new Exception(
                'Email already registered'
            );
        }

        $hashedPassword = Hash::make($password);

        return $this->userRepository->create([
            'name'     => $name,
            'email'    => $email,
            'password' => $hashedPassword
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */
    public function login(
        string $email,
        string $password,
        string $jwtSecret,
        int $tenantId
    ): array {

        $user =
            $this->userRepository->findByEmail($email);

        if ($user === false) {
            throw new Exception(
                'Invalid email or password'
            );
        }

        if (
            !Hash::verify(
                $password,
                $user['password']
            )
        ) {
            throw new Exception(
                'Invalid email or password'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | JWT Payload
        |--------------------------------------------------------------------------
        */

        $payload = [
            'user_id'   => (int) $user['id'],
            'tenant_id' => $tenantId
        ];


        /*
        |--------------------------------------------------------------------------
        | Access Token
        |--------------------------------------------------------------------------
        */

        $accessToken =
            JWT::generateAccessToken(
                $payload,
                $jwtSecret
            );


        /*
        |--------------------------------------------------------------------------
        | Session
        |--------------------------------------------------------------------------
        */

        $_SESSION['access_token'] = $accessToken;
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['tenant_id'] = $tenantId;


        /*
        |--------------------------------------------------------------------------
        | CSRF Token
        |--------------------------------------------------------------------------
        */

        $csrfToken = CSRF::generate();

        $_SESSION['csrf_token'] = $csrfToken;


        /*
        |--------------------------------------------------------------------------
        | Refresh Token
        |--------------------------------------------------------------------------
        */

        $refreshToken =
            JWT::generateRefreshToken(
                $payload,
                $jwtSecret
            );


        /*
        |--------------------------------------------------------------------------
        | Store Refresh Token Hash
        |--------------------------------------------------------------------------
        */

        $tokenHash =
            hash(
                'sha256',
                $refreshToken
            );


        /*
        |--------------------------------------------------------------------------
        | Refresh Token Expiry
        |--------------------------------------------------------------------------
        */

        $refreshExpiry =
            (int) ($_ENV['JWT_REFRESH_EXPIRY'] ?? 604800);

        $expiresAt = date(
            'Y-m-d H:i:s',
            time() + $refreshExpiry
        );


        $this->refreshTokenRepository->create(
            (int) $user['id'],
            $tokenHash,
            $expiresAt
        );


        /*
        |--------------------------------------------------------------------------
        | Remove Password From Response
        |--------------------------------------------------------------------------
        */

        unset($user['password']);


        return [
            'user'         => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | CHANGE PASSWORD
    |--------------------------------------------------------------------------
    */
    public function changePassword(
        int $userId,
        string $currentPassword,
        string $newPassword
    ): void {

        $user =
            $this->userRepository->findById($userId);

        if ($user === false) {
            throw new Exception(
                'User not found'
            );
        }

        if (
            !Hash::verify(
                $currentPassword,
                $user['password']
            )
        ) {
            throw new Exception(
                'Current password is incorrect'
            );
        }

        $hashedPassword =
            Hash::make($newPassword);


        if (
            !$this->userRepository->updatePassword(
                $userId,
                $hashedPassword
            )
        ) {
            throw new Exception(
                'Failed to update password'
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

        if (
            !$this->refreshTokenRepository
                ->revokeAllForUser($userId)
        ) {
            throw new Exception(
                'Failed to logout'
            );
        }

        session_unset();
        session_destroy();
    }


    /*
    |--------------------------------------------------------------------------
    | REFRESH TOKEN
    |--------------------------------------------------------------------------
    */
    public function refresh(
        string $refreshToken,
        string $jwtSecret
    ): array {

        if (trim($refreshToken) === '') {
            throw new Exception(
                'Refresh token is required'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Find Token In Database
        |--------------------------------------------------------------------------
        */

        $tokenHash =
            hash(
                'sha256',
                $refreshToken
            );

        $token =
            $this->refreshTokenRepository
                ->findByTokenHash($tokenHash);

        if ($token === false) {
            throw new Exception(
                'Invalid or revoked refresh token'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Check Database Expiry
        |--------------------------------------------------------------------------
        */

        if (
            strtotime($token['expires_at']) < time()
        ) {
            throw new Exception(
                'Refresh token expired'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Verify JWT
        |--------------------------------------------------------------------------
        */

        $payload =
            JWT::verify(
                $refreshToken,
                $jwtSecret
            );

        if ($payload === false) {
            throw new Exception(
                'Invalid refresh token'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Required Claims
        |--------------------------------------------------------------------------
        */

        if (
            !isset($payload['user_id']) ||
            !isset($payload['tenant_id'])
        ) {
            throw new Exception(
                'Invalid refresh token payload'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Match DB User With JWT User
        |--------------------------------------------------------------------------
        */

        if (
            (int) $token['user_id'] !==
            (int) $payload['user_id']
        ) {
            throw new Exception(
                'Invalid refresh token'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Revoke Old Refresh Token
        |--------------------------------------------------------------------------
        */

        if (
            !$this->refreshTokenRepository->revoke(
                (int) $token['id']
            )
        ) {
            throw new Exception(
                'Failed to rotate refresh token'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | New JWT Payload
        |--------------------------------------------------------------------------
        */

        $newPayload = [
            'user_id'   => (int) $payload['user_id'],
            'tenant_id' => (int) $payload['tenant_id']
        ];


        /*
        |--------------------------------------------------------------------------
        | Generate New Access Token
        |--------------------------------------------------------------------------
        */

        $accessToken =
            JWT::generateAccessToken(
                $newPayload,
                $jwtSecret
            );


        /*
        |--------------------------------------------------------------------------
        | Generate New Refresh Token
        |--------------------------------------------------------------------------
        */

        $newRefreshToken =
            JWT::generateRefreshToken(
                $newPayload,
                $jwtSecret
            );


        /*
        |--------------------------------------------------------------------------
        | Store New Refresh Token
        |--------------------------------------------------------------------------
        */

        $newTokenHash =
            hash(
                'sha256',
                $newRefreshToken
            );

        $refreshExpiry =
            (int) ($_ENV['JWT_REFRESH_EXPIRY'] ?? 604800);

        $newExpiresAt = date(
            'Y-m-d H:i:s',
            time() + $refreshExpiry
        );


        $this->refreshTokenRepository->create(
            (int) $payload['user_id'],
            $newTokenHash,
            $newExpiresAt
        );


        /*
        |--------------------------------------------------------------------------
        | Update Session
        |--------------------------------------------------------------------------
        */

        $_SESSION['access_token'] = $accessToken;
        $_SESSION['user_id'] =
            (int) $payload['user_id'];

        $_SESSION['tenant_id'] =
            (int) $payload['tenant_id'];


        /*
        |--------------------------------------------------------------------------
        | Generate New CSRF Token
        |--------------------------------------------------------------------------
        */

        $csrfToken = CSRF::generate();

        $_SESSION['csrf_token'] = $csrfToken;


        return [
            'access_token'  => $accessToken,
            'refresh_token' => $newRefreshToken
        ];
    }
}