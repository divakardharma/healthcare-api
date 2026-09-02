<?php

require_once __DIR__ . '/app/Config/config.php';
require_once __DIR__ . '/app/Security/AES.php';

$data = [
  'refresh_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJ0ZW5hbnRfaWQiOjEsImlhdCI6MTc4ODM1MTMyOSwiZXhwIjoxNzg4OTU2MTI5fQ.Kn7avuEDzQAyBiCW8fIrYLBR7pdYL3B36oEmZHuUoLE'
];

$json = json_encode($data);

$encrypted = AES::encrypt(
    $json,
    $_ENV['AES_KEY']
);

echo $encrypted;