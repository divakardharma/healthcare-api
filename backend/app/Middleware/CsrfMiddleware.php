<?php

require_once __DIR__ . '/../Security/CSRF.php';
require_once __DIR__ . '/../Helpers/Response.php';

class CsrfMiddleware
{
    public static function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            return;
        }

        $headers = getallheaders();

        $normalizedHeaders = [];

        foreach ($headers as $name => $value) {
            $normalizedHeaders[strtolower($name)] = $value;
        }

        if (!isset($normalizedHeaders['x-csrf-token'])) {
            Response::error('CSRF token required', 403);
        }

        $token = trim($normalizedHeaders['x-csrf-token']);

        if ($token === '') {
            Response::error('CSRF token required', 403);
        }

        $storedToken = $_SESSION['csrf_token'] ?? null;

        if (
            $storedToken === null ||
            !CSRF::verify($token, $storedToken)
        ) {
            Response::error('Invalid CSRF token', 403);
        }
    }
}