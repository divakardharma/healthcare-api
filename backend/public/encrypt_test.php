<?php

require_once __DIR__ . '/../app/Security/AES.php';
require_once __DIR__ . '/../app/Config/config.php';

$data = [
'status' => 'Cancelled'

];

$json = json_encode($data);

$key = $_ENV['AES_KEY'] ?? '';

if ($key === '') {
    die("ERROR: AES_KEY not loaded");
}

try {

    $encrypted = AES::encrypt($json, $key);

    echo "<h3>Encrypted Data:</h3>";

    echo "<div style='
        max-width: 100%;
        box-sizing: border-box;
        padding: 15px;
        background: #f5f5f5;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-family: monospace;
        line-height: 1.5;
        white-space: pre-wrap;
        word-break: break-all;
        overflow-wrap: anywhere;
    '>";

    echo htmlspecialchars($encrypted);

    echo "</div>";

} catch (Exception $e) {

    echo "Encryption failed: " . htmlspecialchars($e->getMessage());

}

?>