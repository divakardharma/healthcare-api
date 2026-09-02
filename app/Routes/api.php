<?php

require_once __DIR__ . '/../Controllers/AuthController.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

$authController = new AuthController();



//--------------------------------------------      REGISTER      -------------------------------------------------


if ($method === 'POST' && str_contains($path, '/register')) {

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    $authController->register($data);

    exit;
}



// --------------------------------------------------     LOGIN     ----------------------------------------------------


if ($method === 'POST' && str_contains($path, '/login')) {

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    $jwtSecret = $_ENV['JWT_SECRET'];

    $authController->login(
        $data,
        $jwtSecret
    );

    exit;
}



// ---------------------------------------------     CHANGE PASSWORD      ---------------------------------------------------


if ($method === 'POST' && str_contains($path, '/change-password')) {

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/TenantMiddleware.php';

$jwtSecret = $_ENV['JWT_SECRET'];

$payload = AuthMiddleware::handle($jwtSecret);

$userTenantId = (int) $payload['tenant_id'];

TenantMiddleware::validate(
    $userTenantId,
    $userTenantId
);

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    $userId = (int) $payload['user_id'];

    $authController->changePassword(
        $data,
        $userId
    );

    exit;
}


 
// -----------------------------------------------         LOGOUT       ---------------------------------------------------------


if ($method === 'POST' && str_contains($path, '/logout')) {

    require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

    $jwtSecret = $_ENV['JWT_SECRET'];

    // Validate access token
    $payload = AuthMiddleware::handle($jwtSecret);

    // Get logged-in user's ID
    $userId = (int) $payload['user_id'];

    $authController->logout($userId);

    exit;
}



// --------------------------------------------       ROUTE NOT FOUND        -------------------------------------------------------


http_response_code(404);

echo json_encode([
    'status' => false,
    'message' => 'Route not found'
]);