<?php

require_once __DIR__ . '/../Security/JWT.php';

class AuthMiddleware
{
    public static function handle(string $secret): array
    {
        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {

            http_response_code(401);
            echo json_encode([
                'status' => false,
                'message' => 'Authorization token required'
            ]);

            exit;
        }

        $authorization = $headers['Authorization'];

        if (!str_starts_with($authorization, 'Bearer ')) {

            http_response_code(401);
            echo json_encode([
                'status' => false,
                'message' => 'Invalid authorization format'
            ]);

            exit;
        }

        $token = substr($authorization, 7);

        $payload = JWT::verify($token, $secret);

        if ($payload === false) {
            
            http_response_code(401);
            echo json_encode([
                'status' => false,
                'message' => 'Invalid or expired token'
            ]);

            exit;
        }

        return $payload;
    }
}