
<?php

require_once __DIR__ . '/../app/Security/AES.php';
require_once __DIR__ . '/../app/Config/config.php';

$encryptionKey = $_ENV['AES_KEY'];

$encryptedPayload = 'LBmQFJXDeccEN0PG3H/O0HKdBpROCtTEXqzsKwCr23whe22IeBY6NTI///vhl6wZ7/Ppb6m2HuLzD1+1GosVXws0ysx9InnM4Cb7A2yagG/c8fRDT5RLhVxQIftvoIo81+w/skF6us/ffngAB791Wf1E3t6TKhhTFUt8Ljd32vwKzOlFHNfkcLBHjfbAjC5wqR159L79cUBIuS8E321ezi2T4amjThf1oyDOa7XOqX6985mQkNHp65ZJeUkzXE7aBSKHB4Fuetr0Kmq38MP6LlH8V+p+5Vt41WuJk3tEDu4=';

$decrypted = AES::decrypt($encryptedPayload, $encryptionKey);

if ($decrypted === false) {
    echo json_encode([
        "error" => "Decryption failed"
    ], JSON_PRETTY_PRINT);
    exit;
}

$decoded = json_decode($decrypted, true);

if ($decoded === null) {
    echo json_encode([
        "error" => "Invalid decrypted JSON",
        "raw" => $decrypted
    ], JSON_PRETTY_PRINT);
    exit;
}

header('Content-Type: application/json');

echo json_encode($decoded, JSON_PRETTY_PRINT);