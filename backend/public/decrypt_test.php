<?php

require_once __DIR__ . '/../app/Security/AES.php';
require_once __DIR__ . '/../app/Config/config.php';

$encryptedPayload =      "Pt5eTle8jOtCjpjxpcKr1nZEoQqjPtM+/n0dhjM8tWjlFIoFaPJ8uUgMZ7G7TMXkFmvVOKoaJj53p1sq0hJkh/7kHRPavCByy3/juJzSQRB5AzMfMviSwjSiOsIuU3gOyjh6nRrdNNWKleUGm4S2Bg==";

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