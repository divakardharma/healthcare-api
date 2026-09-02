<?php

require_once __DIR__ . '/../Security/CSRF.php';

class CsrfMiddleware
{
    public static function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // GET requests usually don't change data,
        // so CSRF validation is not required.
        if ($method === 'GET') {
            return;
        }

        $headers = getallheaders();

        if (!isset($headers['X-CSRF-Token'])) {

            http_response_code(403);
            echo json_encode([
                'status' => false,
                'message' => 'CSRF token required'
            ]);

            exit;
        }

        $token = $headers['X-CSRF-Token'];

        $storedToken = $_SESSION['csrf_token'] ?? null;

        if ($storedToken === null ||!CSRF::verify($token, $storedToken)) {

            http_response_code(403);
            echo json_encode([
                'status' => false,
                'message' => 'Invalid CSRF token'
            ]);

            exit;
        }
    }
}