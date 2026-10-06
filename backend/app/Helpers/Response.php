<?php

require_once __DIR__ . '/../Security/AES.php';

class Response
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200,
        ?array $extra = null
    ): void {

        http_response_code($code);

        $response = [
            'status' => true,
            'message' => $message,
            'data' => $data
        ];

        // Optional top-level keys (e.g. 'pagination'). Existing callers
        // pass nothing, so their response shape is unchanged.
        if ($extra !== null) {
            $response = array_merge($response, $extra);
        }

        $body = json_encode($response) ?: '{}';
        $aesKey = (string) ($_ENV['AES_KEY'] ?? '');

        $encrypted = AES::encrypt(
            $body,
            $aesKey
        );

        echo json_encode([
            'payload' => $encrypted
        ]);

        exit;
    }

    public static function error(
        string $message = 'Something went wrong',
        int $code = 400,
        mixed $errors = null
    ): void {

        http_response_code($code);

        $response = [
            'status' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        $body = json_encode($response) ?: '{}';
        $aesKey = (string) ($_ENV['AES_KEY'] ?? '');

        $encrypted = AES::encrypt(
            $body,
            $aesKey
        );

        echo json_encode([
            'payload' => $encrypted
        ]);

        exit;
    }
}