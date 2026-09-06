
<?php

require_once __DIR__ . '/../app/Security/AES.php';
require_once __DIR__ . '/../app/Config/config.php';

$encryptionKey = $_ENV['AES_KEY'];

$encryptedPayload = 'fG0UzSYRYsHntJQEOXkqL3kKB18B8yCKVJLVt4kDXVCBo+7CoxgNpCssNuV8tUXuJlutaFm1Rq5iatKqoYjKDXqau3IRHGZfDcfd+ykroNfG2AWg9nLG3SvLkFN1ogPDOlhDMqlahTCQMMQkuiwRAVZC2um/ryt2G7PsfsUgqJluLLEv/1W7mSq48rX79E5n4B/ktnTqiTQ/TO2fRA8QflRRLHisRMuZuY5g0wx+7Pf0cWpANFHID5BAdPX8Q8qdVsrVVQGX0qpXi0DhVgtCTyX6mgLt+lMTwISJngvhKp0igW8PboYQhAoQgE+k3ATospIX+JR1iFnbhfasraz93eaoFN1NxXFLX+h7yU+YZqJsrdTjWAxfHMXO3+RqMua7HNe6T8PXcJhESp1DKH4GZfXTpdGueNz2Hctb/K2Bhw4BlfqTK7WnlIvzL+k3vEOZ0XIoPy47IKLUi7CLqddjvPJCjJ0h/6bgZTQ4N1oJDPY=';

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