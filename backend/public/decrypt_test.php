
<?php

require_once __DIR__ . '/../app/Security/AES.php';
require_once __DIR__ . '/../app/Config/config.php';

$encryptionKey = $_ENV['AES_KEY'];

$encryptedPayload = 'BtAELwJy5xact5ZMIPCyVBXD08dbdyyanpm0tmq8scB87LNXjydiLc3F1ps2Rlkd7kMJJ4Z2o2vsClkhAP1GNlfS4mshLWOk87LNS+nk3mPBRSKJBLDZiWsxNcS2uKQOgA/V0JdhYpRlnew/oFrXBcaYB28h9xLgVbv2zJlKZIhflTfe2I1ggf2/GTTnG1xkzeocHSrxWZi4lwq03DbZaw==';

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