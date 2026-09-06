<?php

require_once __DIR__ . '/../app/Security/AES.php';
require_once __DIR__ . '/../app/Config/config.php';

$data = [
  
    //  "subdomain" => "abc",

    // "refresh_token"=> "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJ0ZW5hbnRfaWQiOjEsImlhdCI6MTc4ODY3NzI0MiwiZXhwIjoxNzg5MjgyMDQyfQ.ifYlfNnQIq9-AfNn-QK83wSjZhkVDRP55SDVrSpkHnQ"

    // "name" => "ABC Hospital",
    // "email" => "admin@abc.com",
    "subdomain"=>"abc",
    // "password" => "abc@12345"


    //   "name"=> "Dr. Kumar",
    "email"=> "doctor@abc.com",
    "password"=> "Doctor@123",
    // "role"=> "Provider"
];

$json = json_encode($data);

$encryptionKey = $_ENV['AES_KEY'];

$encrypted = AES::encrypt($json, $encryptionKey);
$decrypted = AES::decrypt($encrypted, $encryptionKey);

echo "<h3>Original JSON</h3>";
echo "<pre>";
echo $json;
echo "</pre>";

echo "<h3>Encrypted Payload</h3>";
echo "<pre>";
echo $encrypted;
echo "</pre>";

echo "<h3>Decrypted JSON</h3>";
echo "<pre>";
echo $decrypted;
echo "</pre>";