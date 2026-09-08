<?php

class AES
{
    private static string $cipher = 'AES-256-CBC';

    public static function encrypt(string $data, string $key): string
    {
        $iv = random_bytes(openssl_cipher_iv_length(self::$cipher));

        $encrypted = openssl_encrypt(
            $data,
            self::$cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($iv . $encrypted);
    }

    public static function decrypt(string $data, string $key): string|false
    {
        $data = base64_decode($data, true);

        if ($data === false) {
            return false;
        }

        $ivLength = openssl_cipher_iv_length(self::$cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        return openssl_decrypt(
            $encrypted,
            self::$cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }

    public static function encryptField(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return self::encrypt($value, self::dbKey());
    }

    public static function decryptField(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $decrypted = self::decrypt($value, self::dbKey());

        // If decryption fails (e.g. legacy plaintext row from before this
        // change), fall back to returning the raw value instead of null so
        // existing data doesn't just disappear from responses.
        return $decrypted === false ? $value : $decrypted;
    }

    /**
     * Deterministic hash used ONLY for equality lookups (e.g. find user by
     * email). Never used to store the actual value - always alongside the
     * AES-encrypted column.
     */
    public static function searchHash(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return hash_hmac(
            'sha256',
            strtolower(trim($value)),
            self::hashKey()
        );
    }

    private static function dbKey(): string
    {
        $key = (string) ($_ENV['DB_ENCRYPTION_KEY'] ?? '');

        if ($key === '') {
            throw new Exception('DB_ENCRYPTION_KEY is not configured');
        }

        return $key;
    }

    private static function hashKey(): string
    {
        $key = (string) ($_ENV['DB_HASH_KEY'] ?? '');

        if ($key === '') {
            throw new Exception('DB_HASH_KEY is not configured');
        }

        return $key;
    }
}