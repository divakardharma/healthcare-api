<?php

$envPath = __DIR__ . '/../../../.env';

if (file_exists($envPath)) {

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {

        $line = trim($line);

        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        if (
            strlen($value) >= 2 &&
            (
                ($value[0] === '"' && $value[-1] === '"') ||
                ($value[0] === "'" && $value[-1] === "'")
            )
        ) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
    }
}