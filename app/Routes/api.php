<?php

require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Controllers/UserController.php';
require_once __DIR__ . '/../Controllers/PatientController.php';
require_once __DIR__ . '/../Controllers/AppointmentController.php';
require_once __DIR__ . '/../Controllers/CalendarController.php';

require_once __DIR__ . '/../Services/UserService.php';
require_once __DIR__ . '/../Services/PatientService.php';
require_once __DIR__ . '/../Services/AppointmentService.php';
require_once __DIR__ . '/../Services/CalendarService.php';

require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/RoleRepository.php';
require_once __DIR__ . '/../Repositories/PatientRepository.php';
require_once __DIR__ . '/../Repositories/AppointmentRepository.php';

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../Middleware/TenantMiddleware.php';
require_once __DIR__ . '/../Middleware/RoleMiddleware.php';

require_once __DIR__ . '/../Security/AES.php';
require_once __DIR__ . '/../Security/CSRF.php';
require_once __DIR__ . '/../Helpers/Response.php';

require_once __DIR__ . '/../Config/database.php';

global $pdo;



//--------------------------------------         Request Information             -----------------------------------------------

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


/*
|--------------------------------------------------------------------------
| Helper: Read + Decrypt Request Payload
|--------------------------------------------------------------------------
|
| Expected request:
|
| {
|     "csrf_token": "...",
|     "payload": "ENCRYPTED_DATA"
| }
|
*/

function getEncryptedData(): array
{
    $body = json_decode(file_get_contents('php://input'), true);

    if (!is_array($body)) {
        Response::error('Invalid JSON request', 400);
    }

    if (!isset($body['payload']) || !is_string($body['payload'])) {
        Response::error('Encrypted payload required', 400);
    }

    $aesKey = (string) ($_ENV['AES_KEY'] ?? '');

    if ($aesKey === '') {
        Response::error('AES key is not configured', 500);
    }

    $decrypted = AES::decrypt($body['payload'], $aesKey);

    if ($decrypted === false) {
        Response::error('Invalid encrypted payload', 400);
    }

    $data = json_decode($decrypted, true);

    if (!is_array($data)) {
        Response::error('Invalid decrypted data', 400);
    }

    return $data;
}




//------------------------------------------    (GET)    CSRF Token Endpoint         -----------------------------------------


if ($method === 'GET' && str_contains($path, '/csrf-token')) {

    $token = CSRF::generate();

    $_SESSION['csrf_token'] = $token;

    echo json_encode([
        'status'  => true,
        'message' => 'CSRF token generated',
        'data'    => [
            'csrf_token' => $token
        ]
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Public Authentication Routes
|--------------------------------------------------------------------------
|
| Register / Login / Refresh
|
*/

$isPublicRoute =
    str_contains($path, '/register') ||
    str_contains($path, '/login') ||
    str_contains($path, '/refresh');


/*
|--------------------------------------------------------------------------
| CSRF Validation
|--------------------------------------------------------------------------
|
| All protected requests require CSRF.
|
*/

if (!$isPublicRoute) {
    CsrfMiddleware::handle();
}


/*
|--------------------------------------------------------------------------
| Authentication Controller
|--------------------------------------------------------------------------
*/

$authController = new AuthController();


/*
|--------------------------------------------------------------------------
| REGISTER
|--------------------------------------------------------------------------
|
| POST /register
|
*/

if ($method === 'POST' && str_contains($path, '/register')) {

    $data = getEncryptedData();

    $authController->register($data);

    exit;
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
|
| POST /login
|
*/

if ($method === 'POST' && str_contains($path, '/login')) {

    $data = getEncryptedData();

    $jwtSecret = $_ENV['JWT_SECRET'];

    $authController->login($data, $jwtSecret);

    exit;
}


/*
|--------------------------------------------------------------------------
| REFRESH TOKEN
|--------------------------------------------------------------------------
|
| POST /refresh
|
*/

if ($method === 'POST' && str_contains($path, '/refresh')) {

    $data = getEncryptedData();

    $jwtSecret = $_ENV['JWT_SECRET'];

    $authController->refresh($data, $jwtSecret);

    exit;
}


/*
|--------------------------------------------------------------------------
| AUTHENTICATE PROTECTED REQUEST
|--------------------------------------------------------------------------
*/

$jwtSecret = $_ENV['JWT_SECRET'];

$payload = AuthMiddleware::handle($jwtSecret);

$userId   = (int) $payload['user_id'];
$tenantId = (int) $payload['tenant_id'];


/*
|--------------------------------------------------------------------------
| TENANT VALIDATION
|--------------------------------------------------------------------------
|
| JWT tenant_id is the source of truth.
|
*/

TenantMiddleware::validate(
    $tenantId,
    (int) $payload['tenant_id']
);


/*
|--------------------------------------------------------------------------
| Repositories
|--------------------------------------------------------------------------
*/

$userRepository        = new UserRepository($pdo);
$roleRepository        = new RoleRepository($pdo);
$patientRepository     = new PatientRepository($pdo);
$appointmentRepository = new AppointmentRepository($pdo);


/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$userService = new UserService(
    $userRepository,
    $roleRepository
);

$patientService = new PatientService(
    $patientRepository
);

$appointmentService = new AppointmentService(
    $appointmentRepository
);

$calendarService = new CalendarService(
    $appointmentRepository
);


/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/

$userController = new UserController( $userService);

$patientController = new PatientController( $patientService);

$appointmentController = new AppointmentController( $appointmentService);

$calendarController = new CalendarController($calendarService);


/*
|--------------------------------------------------------------------------
| MODULE 11 — CHANGE PASSWORD
|--------------------------------------------------------------------------
|
| POST /change-password
|
*/

if (
    $method === 'POST' &&
    str_contains($path, '/change-password')
) {

    $data = getEncryptedData();
    $authController->changePassword( $data, $userId );

    exit;
}


/*
|--------------------------------------------------------------------------
| MODULE 11 — LOGOUT
|--------------------------------------------------------------------------
|
| POST /logout
|
*/

if ($method === 'POST' && str_contains($path, '/logout')) {

    $authController->logout($userId);

    exit;
}


/*
|--------------------------------------------------------------------------
| MODULE 2 — USER MANAGEMENT
|--------------------------------------------------------------------------
*/


/*
| GET /profile
*/

if ( $method === 'GET' && str_contains($path, '/profile')) {

    $userController->profile( $userId, $tenantId);
    exit;
}


/*
| PUT /profile
*/

if ( $method === 'PUT' && str_contains($path, '/profile')) {

    $data = getEncryptedData();

    $userController->updateProfile( $data, $userId, $tenantId );

    exit;
}


/*
| POST /users
| Admin
*/

if ( $method === 'POST' && preg_match('#/users/?$#', $path)) {

    RoleMiddleware::handle( $payload, ['Admin'] );
    $data = getEncryptedData();
    $userController->create( $data, $tenantId );

    exit;
}


/*
| GET /users
| Admin
*/

if ( $method === 'GET' && preg_match('#/users/?$#', $path)) {

    RoleMiddleware::handle( $payload, ['Admin'] );

    $userController->index( $tenantId);

    exit;
}


/*
| POST /users/{id}/roles
| Admin
*/

if ($method === 'POST' &&preg_match('#/users/(\d+)/roles/?$#', $path, $matches)) {

    RoleMiddleware::handle($payload,['Admin']);
    $data = getEncryptedData();
    $userController->assignRole( (int) $matches[1],  $data,  $tenantId );

    exit;
}


/*
| DELETE /users/{id}/roles/{role}
| Admin
*/

if ( $method === 'DELETE' && preg_match('#/users/(\d+)/roles/([^/]+)/?$#', $path, $matches)) {

    RoleMiddleware::handle( $payload, ['Admin'] );

    $role = urldecode($matches[2]);

    $userController->removeRole((int) $matches[1],$role,$tenantId );

    exit;
}


/*
| PUT /users/{id}
| Admin
*/

if ( $method === 'PUT' && preg_match('#/users/(\d+)/?$#', $path, $matches)) {

    RoleMiddleware::handle( $payload, ['Admin']);
    $data = getEncryptedData();
    $userController->update( (int) $matches[1], $data, $tenantId);

    exit;
}


/*
| DELETE /users/{id}
| Admin
*/

if ( $method === 'DELETE' && preg_match('#/users/(\d+)/?$#', $path, $matches)) {

    RoleMiddleware::handle( $payload, ['Admin'] );
    $userController->delete( (int) $matches[1], $tenantId );

    exit;
}


/*
| GET /users/{id}
| Admin
*/

if ($method === 'GET' &&preg_match('#/users/(\d+)/?$#', $path, $matches)) {

    RoleMiddleware::handle( $payload, ['Admin'] );
    $userController->show( (int) $matches[1], $tenantId );

    exit;
}


/*
|--------------------------------------------------------------------------
| MODULE 3 — PATIENT MANAGEMENT
|--------------------------------------------------------------------------
*/


/*
| GET /patients
|
| Must come BEFORE /patients/{id}
*/

if ( $method === 'GET' && preg_match('#/patients/?$#', $path)) {

    RoleMiddleware::handle($payload,  ['Admin', 'Provider', 'Nurse']);
    $patientController->index( $tenantId);

    exit;
}


/*
| POST /patients
*/

if ( $method === 'POST' &&preg_match('#/patients/?$#', $path)) {

    RoleMiddleware::handle( $payload, ['Admin', 'Provider', 'Nurse']);
    $data = getEncryptedData();
    $patientController->create( $data, $tenantId);

    exit;
}


/*
| GET /patients/{id}
*/

if ( $method === 'GET' && preg_match('#/patients/(\d+)/?$#', $path, $matches)) {

    RoleMiddleware::handle($payload,['Admin', 'Provider', 'Nurse']);
    $patientController->show( (int) $matches[1], $tenantId);

    exit;
}


/*
| PUT /patients/{id}
*/

if ( $method === 'PUT' && preg_match('#/patients/(\d+)/?$#', $path, $matches)) {

    RoleMiddleware::handle( $payload, ['Admin', 'Provider', 'Nurse']);
    $data = getEncryptedData();
    $patientController->update( (int) $matches[1], $data, $tenantId);

    exit;
}


/*
| DELETE /patients/{id}
*/

if ( $method === 'DELETE' && preg_match('#/patients/(\d+)/?$#', $path, $matches)) {

    RoleMiddleware::handle(  $payload,  ['Admin', 'Provider', 'Nurse'] );
    $patientController->delete((int) $matches[1], $tenantId );

    exit;
}


/*
|--------------------------------------------------------------------------
| MODULE 4 — APPOINTMENT MANAGEMENT
|--------------------------------------------------------------------------
*/


/*
| GET /appointments
|
| Must come BEFORE /appointments/{id}
*/

if ( $method === 'GET' && preg_match('#/appointments/?$#', $path)) {

    RoleMiddleware::handle( $payload, ['Admin', 'Provider', 'Nurse', 'Patient'] );
    $appointmentController->index( $tenantId);

    exit;
}


/*
| POST /appointments
*/

if ($method === 'POST' && preg_match('#/appointments/?$#', $path)) {

    RoleMiddleware::handle( $payload, ['Admin', 'Provider', 'Nurse']  );
    $data = getEncryptedData();
    $appointmentController->create( $data, $tenantId );

    exit;
}


/*
| GET /appointments/{id}
*/

if ( $method === 'GET' & preg_match('#/appointments/(\d+)/?$#', $path, $matches)) {

    RoleMiddleware::handle( $payload, ['Admin', 'Provider', 'Nurse', 'Patient']);
    $appointmentController->show( (int) $matches[1], $tenantId);

    exit;
}


/*
| PUT /appointments/{id}
*/

if ($method === 'PUT' &&preg_match('#/appointments/(\d+)/?$#', $path, $matches)) {

    RoleMiddleware::handle( $payload, ['Admin', 'Provider', 'Nurse']);
    $data = getEncryptedData();
    $appointmentController->update( (int) $matches[1], $data, $tenantId);

    exit;
}


/*
| PATCH /appointments/{id}/status
*/

if ( $method === 'PATCH' && preg_match('#/appointments/(\d+)/status/?$#', $path, $matches)) {

    RoleMiddleware::handle( $payload, ['Admin', 'Provider', 'Nurse']);
    $data = getEncryptedData();
    $appointmentController->updateStatus((int) $matches[1],$data,$tenantId );

    exit;
}


/*
| PUT /appointments/{id}/cancel
*/

if ( $method === 'PUT' && preg_match('#/appointments/(\d+)/cancel/?$#', $path, $matches)) {

    RoleMiddleware::handle(  $payload,  ['Admin', 'Provider', 'Nurse'] );
    $appointmentController->cancel( (int) $matches[1], $tenantId );

    exit;
}


/*
|--------------------------------------------------------------------------
| MODULE 10 — CALENDAR
|--------------------------------------------------------------------------
*/


/*
| GET /calendar/day
*/

if ( $method === 'GET' && str_contains($path, '/calendar/day')) {

    RoleMiddleware::handle( $payload, ['Admin', 'Provider', 'Nurse'] );

    $providerId = isset($_GET['provider_id'])
        ? (int) $_GET['provider_id']
        : null;

    $calendarController->dayView(  $tenantId,  $providerId);

    exit;
}


/*
| GET /calendar/range
*/

if ( $method === 'GET' && str_contains($path, '/calendar/range')) {

    RoleMiddleware::handle( $payload,['Admin', 'Provider', 'Nurse'] );

    $providerId = isset($_GET['provider_id'])
        ? (int) $_GET['provider_id']
        : null;

    $calendarController->rangeView( $tenantId, $providerId );

    exit;
}


/*
| GET /calendar/upcoming
*/

if ($method === 'GET' && str_contains($path, '/calendar/upcoming')) {

    RoleMiddleware::handle( $payload, ['Admin', 'Provider', 'Nurse'] );

    $providerId = isset($_GET['provider_id'])
        ? (int) $_GET['provider_id']
        : null;

    $calendarController->upcoming(  $tenantId,  $providerId );

    exit;
}


/*
| GET /calendar/appointments/{id}/tooltip
*/

if ($method === 'GET' &&preg_match( '#/calendar/appointments/(\d+)/tooltip/?$#', $path, $matches )) {

    RoleMiddleware::handle( $payload, ['Admin', 'Provider', 'Nurse'] );
    $calendarController->tooltip( $tenantId, (int) $matches[1] );

    exit;
}


/*
|--------------------------------------------------------------------------
| Route Not Found
|--------------------------------------------------------------------------
*/

Response::error(
    'Route not found',
    404
);