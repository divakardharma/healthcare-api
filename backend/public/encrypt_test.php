<?php

require_once __DIR__ . '/../app/Security/AES.php';
require_once __DIR__ . '/../app/Config/config.php';

$data = [
  
    //  "subdomain" => "abc",

    // "refresh_token"=> "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJ0ZW5hbnRfaWQiOjIsImlhdCI6MTc4ODcwMjczOCwiZXhwIjoxNzg5MzA3NTM4fQ.SvYRy6DXKQ62Dh7l3sXE6tYnYEKKyFTGYk0yJbbYwbI",

    // "name" => "XYZ Hospital",
    // "email" => "admin@xyz.com",
    // "subdomain"=>"xyz",
    // "password" => "xyz@12345"

    // "name"=> "DR Arun Kumar",
// "age"=> 42,
// "gender"=> "Male",
// "phone"=> "9876543210",
// "email"=> "arun@example.com",


    //   "name"=> "nurse1",
    // "email"=> "nurse@abc.com",
    // "password"=> "provider@123",
    // "role"=> "Provider"

   
"appointment_id" =>"1",
"note"=> "Patient reported improvement"

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