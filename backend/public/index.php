<?php

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/Config/config.php';
require_once __DIR__ . '/../app/Config/database.php'; // creates global $pdo
require_once __DIR__ . '/../app/Routes/api.php';