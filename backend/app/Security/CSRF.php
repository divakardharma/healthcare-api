<?php

class CSRF
{
    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function verify(string $token, string $storedToken): bool
    {
        return hash_equals($storedToken, $token);
    }
}