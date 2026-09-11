<?php

require_once __DIR__ . '/config.php';

$host = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];

try {

    $masterPdo = new PDO(
        "mysql:host=$host;dbname=heal_master_db;charset=utf8mb4",
        $username,
        $password
    );

    $masterPdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $masterPdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    die("Master database connection failed: " . $e->getMessage());

}