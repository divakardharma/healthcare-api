<?php

require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/RefreshTokenRepository.php';
require_once __DIR__ . '/../Security/Hash.php';
require_once __DIR__ . '/../Security/JWT.php';

class AuthService
{
    private UserRepository $userRepository;
    private RefreshTokenRepository $refreshTokenRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->refreshTokenRepository = new RefreshTokenRepository();
    }

    public function register(
        string $name,
        string $email,
        string $password,
        int $tenantId
    ): int {
        // Check whether email already exists
        $existingUser = $this->userRepository->findByEmail($email);

        if ($existingUser !== false) {
            throw new Exception('Email already registered');
        }

        // Hash password
        $hashedPassword = Hash::make($password);

        // Create user through repository
        return $this->userRepository->create(
            $tenantId,
            $name,
            $email,
            $hashedPassword
        );
    }

    public function login(
        string $email,
        string $password,
        string $jwtSecret
    ): array {
        // Find user
        $user = $this->userRepository->findByEmail($email);

        if ($user === false) {
            throw new Exception('Invalid email or password');
        }

        // Verify password
        if (!Hash::verify($password, $user['password'])) {
            throw new Exception('Invalid email or password');
        }

        // JWT payload
        $payload = [
            'user_id' => $user['id'],
            'tenant_id' => $user['tenant_id']
        ];

        // Generate access token
        $accessToken = JWT::generateAccessToken(
            $payload,
            $jwtSecret
        );

        // Generate refresh token
        $refreshToken = JWT::generateRefreshToken(
            $payload,
            $jwtSecret
        );

        // Hash refresh token before storing
        $tokenHash = hash('sha256', $refreshToken);

        // Refresh token expires in 7 days
        $expiresAt = date(
            'Y-m-d H:i:s',
            time() + 604800
        );

        // Store refresh token hash
        $this->refreshTokenRepository->create(
            $user['id'],
            $tokenHash,
            $expiresAt
        );

        return [
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ];
    }

    public function changePassword(
        int $userId,
        string $currentPassword,
        string $newPassword
    ): void {
        // Find current user
        $user = $this->userRepository->findById($userId);

        if ($user === false) {
            throw new Exception('User not found');
        }

        // Verify current password
        if (!Hash::verify($currentPassword, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }

        // Hash new password
        $hashedPassword = Hash::make($newPassword);

        // Update password
        if (!$this->userRepository->updatePassword(
            $userId,
            $hashedPassword
        )) {
            throw new Exception('Failed to update password');
        }
    }


    public function logout(int $userId): void
{
    // Revoke all active refresh tokens
    if (!$this->refreshTokenRepository->revokeAllForUser($userId)) {
        throw new Exception('Failed to logout');
    }
}
}