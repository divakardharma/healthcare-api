<?php

require_once __DIR__ . '/../Security/JWT.php';
require_once __DIR__ . '/../Helpers/Response.php';

class AuthMiddleware
{
    public static function handle(string $secret): array
    {
        $headers = getallheaders();

        // Normalize header names
        $normalizedHeaders = [];

        foreach ($headers as $name => $value) {
            $normalizedHeaders[strtolower($name)] = $value;
        }

        if (!isset($normalizedHeaders['authorization'])) {

            Response::error(
                'Authorization token required',
                401
            );
        }

        $authorization = trim(
            $normalizedHeaders['authorization']
        );

        if (!str_starts_with($authorization, 'Bearer ')) {

            Response::error(
                'Invalid authorization format',
                401
            );
        }

        $token = trim(
            substr($authorization, 7)
        );

        if ($token === '') {

            Response::error(
                'Authorization token required',
                401
            );
        }

        // Verify JWT
        $payload = JWT::verify(
            $token,
            $secret
        );

        if ($payload === false) {

            Response::error(
                'Invalid or expired token',
                401
            );
        }

        // Check token belongs to current PHP session
        if (
            !isset($_SESSION['access_token']) ||
            !hash_equals(
                $_SESSION['access_token'],
                $token
            )
        ) {

            Response::error(
                'Session expired or logged out',
                401
            );
        }

        return $payload;
    }
}