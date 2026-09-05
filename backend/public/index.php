<?php

// Single entry point / API Gateway.
// Loads config + DB connection first so every controller/service/middleware
// downstream can rely on $_ENV and the global $pdo being ready.

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/Config/config.php';
require_once __DIR__ . '/../app/Config/database.php'; // creates global $pdo
require_once __DIR__ . '/../app/Routes/api.php';