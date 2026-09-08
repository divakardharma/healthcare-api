
<?php

require_once __DIR__ . '/../app/Security/AES.php';
require_once __DIR__ . '/../app/Config/config.php';

$encryptionKey = $_ENV['AES_KEY'];

$encryptedPayload = 'P70Nkzn9lzgcjnyQsABg3RGFnYvxcv4qBTVmSZY9AsBUnqDuZpqPdg2C71FXv895Kt1CMFMgaTmnsc7fOSa6Rgv1OuMdLu+Z/kx2NkKOJoiBGSznqAbWNa6ShNOzy37DoOQp6gJ5EUy/AbH5OlkDDg==';

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