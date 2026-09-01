<?php

class Response
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200
    ): void {
        http_response_code($code);

        echo json_encode([
            'status' => true,
            'message' => $message,
            'data' => $data
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

        echo json_encode($response);

        exit;
    }
}