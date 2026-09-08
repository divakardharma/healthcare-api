<?php

require_once __DIR__ . '/../app/Security/AES.php';
require_once __DIR__ . '/../app/Config/config.php';

// $data = [
  
//     //  "subdomain" => "abc",

//     // "refresh_token"=> "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJ0ZW5hbnRfaWQiOjEsImlhdCI6MTc4ODY3NzI0MiwiZXhwIjoxNzg5MjgyMDQyfQ.ifYlfNnQIq9-AfNn-QK83wSjZhkVDRP55SDVrSpkHnQ"

//     // "name" => "ABC Hospital",
//     // "email" => "admin@abc.com",
//     "subdomain"=>"abc",
//     // "password" => "abc@12345"

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

try {

    $encrypted = AES::encrypt($json, $key);

    echo "<h3>Encrypted Data:</h3>";

    echo "<div style='
        max-width: 100%;
        box-sizing: border-box;
        padding: 15px;
        background: #f5f5f5;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-family: monospace;
        line-height: 1.5;
        white-space: pre-wrap;
        word-break: break-all;
        overflow-wrap: anywhere;
    '>";

    echo htmlspecialchars($encrypted);

    echo "</div>";

} catch (Exception $e) {

    echo "Encryption failed: " . htmlspecialchars($e->getMessage());

}

?>