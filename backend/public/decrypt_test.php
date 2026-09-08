<?php

require_once __DIR__ . '/../app/Security/AES.php';
require_once __DIR__ . '/../app/Config/config.php';

$encryptionKey = $_ENV['AES_KEY'];

$encryptedPayload = 'P70Nkzn9lzgcjnyQsABg3RGFnYvxcv4qBTVmSZY9AsBUnqDuZpqPdg2C71FXv895Kt1CMFMgaTmnsc7fOSa6Rgv1OuMdLu+Z/kx2NkKOJoiBGSznqAbWNa6ShNOzy37DoOQp6gJ5EUy/AbH5OlkDDg==';

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