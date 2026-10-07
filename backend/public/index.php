<?php

// Load .env first (needed for COOKIE_SECURE before session starts)
require_once __DIR__ . '/../app/Config/config.php';

header('Content-Type: application/json');

// CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (preg_match('/^http:\/\/(?:[a-z0-9-]+\.)?localhost:(3000|3001)$/i', $origin)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Vary: Origin");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");

// Handle browser preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Start PHP session
if (session_status() === PHP_SESSION_NONE) {

    // "false" string-ah correct-ah boolean-ah maatha (bool)"false" === true bug-ah avoid panna
    $cookieSecure = filter_var(
        $_ENV['COOKIE_SECURE'] ?? false,
        FILTER_VALIDATE_BOOLEAN
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $cookieSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// Load backend
require_once __DIR__ . '/../app/Routes/api.php';