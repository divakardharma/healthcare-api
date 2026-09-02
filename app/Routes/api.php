<?php

require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../Security/AES.php';
require_once __DIR__ . '/../Helpers/Response.php';


$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


// ---------------------------------------------
// ENCRYPTED REQUEST DATA
// ---------------------------------------------

function getEncryptedData(): array
{
    $body = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (
        !isset($body['payload']) ||
        !is_string($body['payload'])
    ) {
        Response::error('Encrypted payload required', 400);
    }

    $decrypted = AES::decrypt(
        $body['payload'],
        $_ENV['AES_KEY']
    );

    if ($decrypted === false) {
        Response::error('Invalid encrypted payload', 400);
    }

    $data = json_decode($decrypted, true);

    if (!is_array($data)) {
        Response::error('Invalid decrypted data', 400);
    }

    return $data;
}


// ---------------------------------------------
// CSRF TOKEN
// ---------------------------------------------

if ($method === 'GET' && str_contains($path, '/csrf-token')) {

    require_once __DIR__ . '/../Security/CSRF.php';

    $token = CSRF::generate();

    $_SESSION['csrf_token'] = $token;

    echo json_encode([
        'status' => true,
        'message' => 'CSRF token generated',
        'data' => [
            'csrf_token' => $token
        ]
    ]);

    exit;
}


// ---------------------------------------------
// CSRF VALIDATION
// ---------------------------------------------

if (
    !str_contains($path, '/login') &&
    !str_contains($path, '/register') &&
    !str_contains($path, '/refresh')
) {
    CsrfMiddleware::handle();
}


$authController = new AuthController();


// ---------------------------------------------
// REGISTER
// ---------------------------------------------

if ($method === 'POST' && str_contains($path, '/register')) {

    $data = getEncryptedData();

    $authController->register($data);

    exit;
}


// ---------------------------------------------
// LOGIN
// ---------------------------------------------

if ($method === 'POST' && str_contains($path, '/login')) {

    $data = getEncryptedData();

    $jwtSecret = $_ENV['JWT_SECRET'];

    $authController->login(
        $data,
        $jwtSecret
    );

    exit;
}


// ---------------------------------------------
// REFRESH
// ---------------------------------------------

if ($method === 'POST' && str_contains($path, '/refresh')) {

    $data = getEncryptedData();

    $jwtSecret = $_ENV['JWT_SECRET'];

    $authController->refresh(
        $data,
        $jwtSecret
    );

    exit;
}


// ---------------------------------------------
// CHANGE PASSWORD
// ---------------------------------------------

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

    $data = getEncryptedData();

    $userId = (int) $payload['user_id'];

    $authController->changePassword(
        $data,
        $userId
    );

    exit;
}


// ---------------------------------------------
// LOGOUT
// ---------------------------------------------

if ($method === 'POST' && str_contains($path, '/logout')) {

    require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

    $jwtSecret = $_ENV['JWT_SECRET'];

    $payload = AuthMiddleware::handle($jwtSecret);

    $userId = (int) $payload['user_id'];

    $authController->logout($userId);

    exit;
}


// ---------------------------------------------
// ROUTE NOT FOUND
// ---------------------------------------------

http_response_code(404);

echo json_encode([
    'status' => false,
    'message' => 'Route not found'
]);