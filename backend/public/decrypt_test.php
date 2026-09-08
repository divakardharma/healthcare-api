<?php

require_once __DIR__ . '/../app/Security/AES.php';
require_once __DIR__ . '/../app/Config/config.php';

$encryptionKey = $_ENV['AES_KEY'];

$encryptedPayload = 'BtAELwJy5xact5ZMIPCyVBXD08dbdyyanpm0tmq8scB87LNXjydiLc3F1ps2Rlkd7kMJJ4Z2o2vsClkhAP1GNlfS4mshLWOk87LNS+nk3mPBRSKJBLDZiWsxNcS2uKQOgA/V0JdhYpRlnew/oFrXBcaYB28h9xLgVbv2zJlKZIhflTfe2I1ggf2/GTTnG1xkzeocHSrxWZi4lwq03DbZaw==';

$key = $_ENV['AES_KEY'] ?? '';

if ($key === '') {
    die("ERROR: AES_KEY not loaded");
}

try {

    $decrypted = AES::decrypt($encryptedPayload, $key);

    if ($decrypted === false) {
        die("Decryption failed.");
    }

    echo "<h3>Decrypted Data:</h3>";

    $decoded = json_decode($decrypted, true);

    echo "<div style='
        max-width: 100%;
        box-sizing: border-box;
        overflow-x: auto;
        white-space: pre-wrap;
        word-wrap: break-word;
        overflow-wrap: anywhere;
        background: #f5f5f5;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-family: monospace;
        line-height: 1.5;
    '>";

    if ($decoded !== null) {

        echo htmlspecialchars(
            json_encode(
                $decoded,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

    } else {

        echo htmlspecialchars($decrypted);

    }

    echo "</div>";

} catch (Exception $e) {

    echo "Decryption failed: " . htmlspecialchars($e->getMessage());

}
?>