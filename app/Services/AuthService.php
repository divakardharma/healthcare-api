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

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->refreshTokenRepository = new RefreshTokenRepository();
    }

    //--------------------------------------     Register       ---------------------------------------------------
    public function register( string $name, string $email, string $password, int $tenantId): int {
 
        $existingUser = $this->userRepository->findByEmail($email);

        if ($existingUser !== false) {
            throw new Exception('Email already registered');
        }

        $hashedPassword = Hash::make($password);

        return $this->userRepository->create(
            $tenantId,
            $name,
            $email,
            $hashedPassword
        );
    }

    //--------------------------------------     Login       ---------------------------------------------------
    public function login(  string $email,  string $password,  string $jwtSecret ): array {

        $user = $this->userRepository->findByEmail($email);

        if ($user === false) {
            throw new Exception('Invalid email or password');
        }

        if (!Hash::verify($password, $user['password'])) {
            throw new Exception('Invalid email or password');
        }

        $payload = [
            'user_id' => $user['id'],
            'tenant_id' => $user['tenant_id']
        ];


        $accessToken = JWT::generateAccessToken( $payload, $jwtSecret );

        $_SESSION['access_token'] = $accessToken;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['tenant_id'] = $user['tenant_id'];

        $csrfToken = CSRF::generate();

        $_SESSION['csrf_token'] = $csrfToken;   

        $refreshToken = JWT::generateRefreshToken( $payload, $jwtSecret );

        $tokenHash = hash('sha256', $refreshToken);

        $expiresAt = date( 'Y-m-d H:i:s', time() + 604800 );

        $this->refreshTokenRepository->create(
            $user['id'],
            $tokenHash,
            $expiresAt
        );

        unset($user['password']);

        return [
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ];
    }

    //--------------------------------------     Change Password       ---------------------------------------------------

    public function changePassword(int $userId,  string $currentPassword,  string $newPassword ): void
     {

        $user = $this->userRepository->findById($userId);

        if ($user === false) {
            throw new Exception('User not found');
        }

        if (!Hash::verify($currentPassword, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }

        $hashedPassword = Hash::make($newPassword);


        if (!$this->userRepository->updatePassword(
            $userId,
            $hashedPassword
        )) {
            throw new Exception('Failed to update password');
        }
    }


//--------------------------------------------          Logout          ---------------------------------------------------

public function logout(int $userId): void
{
    if (!$this->refreshTokenRepository->revokeAllForUser($userId)) {
        throw new Exception('Failed to logout');
    }

    session_unset();
    session_destroy();
}   


public function refresh(string $refreshToken, string $jwtSecret): array
{
    $tokenHash = hash('sha256', $refreshToken);

    $token = $this->refreshTokenRepository->findByTokenHash($tokenHash);

    if ($token === false) {
        throw new Exception('Invalid or revoked refresh token');
    }

    if (strtotime($token['expires_at']) < time()) {
        throw new Exception('Refresh token expired');
    }

    // Verify old refresh token
    $payload = JWT::verify($refreshToken, $jwtSecret);

    if ($payload === false) {
        throw new Exception('Invalid refresh token');
    }

    // Revoke old refresh token
    $this->refreshTokenRepository->revoke(
        (int) $token['id']
    );

    // Generate new access token
    $accessToken = JWT::generateAccessToken(
        [
            'user_id' => $payload['user_id'],
            'tenant_id' => $payload['tenant_id']
        ],
        $jwtSecret
    );

    // Generate new refresh token
    $newRefreshToken = JWT::generateRefreshToken(
        [
            'user_id' => $payload['user_id'],
            'tenant_id' => $payload['tenant_id']
        ],
        $jwtSecret
    );

    // Hash new refresh token
    $newTokenHash = hash('sha256', $newRefreshToken);

    // New refresh token expiry
    $newExpiresAt = date(
        'Y-m-d H:i:s',
        time() + 604800
    );

    // Save new refresh token
    $this->refreshTokenRepository->create(
        $payload['user_id'],
        $newTokenHash,
        $newExpiresAt
    );

    // Update session
    $_SESSION['access_token'] = $accessToken;
    $_SESSION['user_id'] = $payload['user_id'];
    $_SESSION['tenant_id'] = $payload['tenant_id'];

    return [
        'access_token' => $accessToken,
        'refresh_token' => $newRefreshToken
    ];
}
}