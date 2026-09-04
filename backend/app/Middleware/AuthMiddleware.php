<?php

require_once __DIR__ . '/../Security/JWT.php';

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

            http_response_code(401);

            echo json_encode([
                'status' => false,
                'message' => 'Authorization token required'
            ]);

            exit;
        }

        $authorization = trim(
            $normalizedHeaders['authorization']
        );

        if (!str_starts_with($authorization, 'Bearer ')) {

            http_response_code(401);

            echo json_encode([
                'status' => false,
                'message' => 'Invalid authorization format'
            ]);

            exit;
        }

        $token = trim(
            substr($authorization, 7)
        );

        if ($token === '') {

            http_response_code(401);

            echo json_encode([
                'status' => false,
                'message' => 'Authorization token required'
            ]);

            exit;
        }

        // Verify JWT
        $payload = JWT::verify(
            $token,
            $secret
        );

        if ($payload === false) {

            http_response_code(401);

            echo json_encode([
                'status' => false,
                'message' => 'Invalid or expired token'
            ]);

            exit;
        }

        // Check token belongs to current PHP session
        if (
            !isset($_SESSION['access_token']) ||
            !hash_equals(
                $_SESSION['access_token'],
                $token
            )
        ) {

            http_response_code(401);

            echo json_encode([
                'status' => false,
                'message' => 'Session expired or logged out'
            ]);

            exit;
        }

        return $payload;
    }
}