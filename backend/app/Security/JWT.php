<?php

class JWT
{
    public static function generateAccessToken( array $payload, string $secret ): string {

        $expiry = (int) ( $_ENV['JWT_ACCESS_EXPIRY'] ?? 900 );

        return self::generate(
            $payload,
            $secret,
            $expiry
        );
    }

    public static function generateRefreshToken(  array $payload, string $secret): string {

        $expiry = (int) ( $_ENV['JWT_REFRESH_EXPIRY'] ?? 604800);

        return self::generate(
            $payload,
            $secret,
            $expiry
        );
    }

    private static function generate( array $payload, string $secret, int $expiry ): string {

        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;

        $headerEncoded = self::base64UrlEncode( json_encode($header));

        $payloadEncoded = self::base64UrlEncode( json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            $secret,
            true
        );

        $signatureEncoded = self::base64UrlEncode( $signature);

        return $headerEncoded . '.'
             . $payloadEncoded . '.'
             . $signatureEncoded;
    }

    public static function verify( string $token,string $secret): array|false {

        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        [$header, $payload, $signature] = $parts;

        // Decode and validate header
        $headerData = json_decode(
            self::base64UrlDecode($header),
            true
        );

        if (!is_array($headerData)) {
            return false;
        }

        if (($headerData['alg'] ?? null) !== 'HS256' ||
            ($headerData['typ'] ?? null) !== 'JWT') 
        {
                
            return false;
        }

        // Verify signature
        $expectedSignature = self::base64UrlEncode(
            hash_hmac(
                'sha256',
                $header . '.' . $payload,
                $secret,
                true
            )
        );

        if (!hash_equals(
            $expectedSignature,
            $signature
        )) {
            return false;
        }

        // Decode payload
        $payloadData = json_decode(
            self::base64UrlDecode($payload),
            true
        );

        if (!is_array($payloadData)) {
            return false;
        }

        // Check expiry
        if (
            isset($payloadData['exp']) &&
            $payloadData['exp'] < time()
        ) {
            return false;
        }

        return $payloadData;
    }

    private static function base64UrlEncode(string $data): string {

        return rtrim(
            strtr(
                base64_encode($data),
                '+/',
                '-_'
            ),
            '='
        );
    }

    private static function base64UrlDecode( string $data): string {

        $data = strtr( $data, '-_', '+/' );

        $padding = strlen($data) % 4;

        if ($padding !== 0) {
            $data .= str_repeat(
                '=',
                4 - $padding
            );
        }

        $decoded = base64_decode(
            $data,
            true
        );

        return $decoded === false
            ? ''
            : $decoded;
    }
}