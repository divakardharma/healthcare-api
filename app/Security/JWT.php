<?php

class JWT
{
    public static function generateAccessToken(array $payload, string $secret): string
    {
        return self::generate($payload, $secret, 900);
    }

    public static function generateRefreshToken(array $payload, string $secret): string
    {
        return self::generate($payload, $secret, 604800);
    }

    private static function generate(
        array $payload,
        string $secret,
        int $expiry
    ): string {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;

        $headerEncoded = self::base64UrlEncode(
            json_encode($header)
        );

        $payloadEncoded = self::base64UrlEncode(
            json_encode($payload)
        );

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            $secret,
            true
        );

        $signatureEncoded = self::base64UrlEncode($signature);

        return $headerEncoded . '.' .
               $payloadEncoded . '.' .
               $signatureEncoded;
    }

    public static function verify(
        string $token,
        string $secret
    ): array|false {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        [$header, $payload, $signature] = $parts;

        $expectedSignature = self::base64UrlEncode(
            hash_hmac(
                'sha256',
                $header . '.' . $payload,
                $secret,
                true
            )
        );

        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        $payloadData = json_decode(
            self::base64UrlDecode($payload),
            true
        );

        if (!$payloadData) {
            return false;
        }

        if (isset($payloadData['exp']) &&
            $payloadData['exp'] < time()) {
            return false;
        }

        return $payloadData;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(
            strtr($data, '-_', '+/')
        );
    }
}