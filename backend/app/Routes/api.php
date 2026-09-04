<?php

require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Controllers/UserController.php';
require_once __DIR__ . '/../Controllers/PatientController.php';
require_once __DIR__ . '/../Controllers/AppointmentController.php';
require_once __DIR__ . '/../Controllers/CalendarController.php';
require_once __DIR__ . '/../Controllers/TenantController.php';

require_once __DIR__ . '/../Services/UserService.php';
require_once __DIR__ . '/../Services/PatientService.php';
require_once __DIR__ . '/../Services/AppointmentService.php';
require_once __DIR__ . '/../Services/CalendarService.php';
require_once __DIR__ . '/../Services/TenantService.php';
require_once __DIR__ . '/../Services/TenantProvisioningService.php';
require_once __DIR__ . '/../Services/TenantResolver.php';

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
require_once __DIR__ . '/../Config/master_database.php';

global $pdo, $masterPdo;

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


/* Read + decrypt request payload */

function getEncryptedData(): array
{
    $body = json_decode(file_get_contents('php://input'), true);

    if (!is_array($body)) {
        Response::error('Invalid JSON request', 400);
    }

    if (!isset($body['payload']) || !is_string($body['payload'])) {
        Response::error('Encrypted payload required', 400);
    }

    $aesKey = (string)($_ENV['AES_KEY'] ?? '');

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


/* GET /csrf-token */

if ($method === 'GET' && str_contains($path, '/csrf-token')) {
    $token = CSRF::generate();
    $_SESSION['csrf_token'] = $token;

    echo json_encode([
        'status' => true,
        'message' => 'CSRF token generated',
        'data' => ['csrf_token' => $token]
    ]);

    exit;
}


/* Public routes */

$isPublicRoute =
    str_contains($path, '/tenant/register') ||
    str_contains($path, '/register') ||
    str_contains($path, '/login') ||
    str_contains($path, '/refresh');


/* CSRF */

if (!$isPublicRoute) {
    CsrfMiddleware::handle();
}


/* POST /tenant/register */

if ($method === 'POST' && preg_match('#/tenant/register/?$#', $path)) {
    $data = getEncryptedData();

    $provisioningService = new TenantProvisioningService($masterPdo);
    $tenantService = new TenantService($masterPdo, $provisioningService);
    $tenantController = new TenantController($tenantService);

    $tenantController->register($data);
    exit;
}


/* POST /login */

if ($method === 'POST' && str_contains($path, '/login')) {
    $data = getEncryptedData();

    $subdomain = strtolower(trim($data['subdomain'] ?? ''));

    if ($subdomain === '') {
        Response::error('Subdomain is required', 422);
    }

    try {
        $tenantResolver = new TenantResolver($masterPdo);
        $tenant = $tenantResolver->resolve($subdomain);
        $tenantPdo = $tenantResolver->connect($tenant);

        $authController = new AuthController($tenantPdo);

        $authController->login(
            $data,
            $_ENV['JWT_SECRET'],
            (int)$tenant['id']
        );
    } catch (Exception $e) {
        Response::error($e->getMessage(), 401);
    }

    exit;
}


/* POST /refresh */

if ($method === 'POST' && str_contains($path, '/refresh')) {
    $data = getEncryptedData();

    $subdomain = strtolower(trim($data['subdomain'] ?? ''));

    if ($subdomain === '') {
        Response::error('Subdomain is required', 422);
    }

    if (empty($data['refresh_token'])) {
        Response::error('Refresh token is required', 422);
    }

    try {
        $tenantResolver = new TenantResolver($masterPdo);
        $tenant = $tenantResolver->resolve($subdomain);
        $tenantPdo = $tenantResolver->connect($tenant);

        $authController = new AuthController($tenantPdo);
        $authController->refresh($data, $_ENV['JWT_SECRET']);
    } catch (Exception $e) {
        Response::error($e->getMessage(), 401);
    }

    exit;
}


/* Authenticate protected request */

$jwtSecret = $_ENV['JWT_SECRET'];
$payload = AuthMiddleware::handle($jwtSecret);

$userId = (int)$payload['user_id'];
$tenantId = (int)$payload['tenant_id'];

TenantMiddleware::validate(
    $tenantId,
    (int)$payload['tenant_id']
);

$tenantResolver = new TenantResolver($masterPdo);
$tenant = $tenantResolver->resolveById($tenantId);
$tenantPdo = $tenantResolver->connect($tenant);


/* Controllers */

$authController = new AuthController($tenantPdo);

$userRepository = new UserRepository($tenantPdo);
$roleRepository = new RoleRepository($tenantPdo);
$patientRepository = new PatientRepository($tenantPdo);
$appointmentRepository = new AppointmentRepository($tenantPdo);

$userService = new UserService($userRepository, $roleRepository);
$patientService = new PatientService($patientRepository);
$appointmentService = new AppointmentService($appointmentRepository);
$calendarService = new CalendarService($appointmentRepository);

$userController = new UserController($userService);
$patientController = new PatientController($patientService);
$appointmentController = new AppointmentController($appointmentService);
$calendarController = new CalendarController($calendarService);


/* POST /change-password */

if ($method === 'POST' && str_contains($path, '/change-password')) {
    $data = getEncryptedData();
    $authController->changePassword($data, $userId);
    exit;
}


/* POST /logout */

if ($method === 'POST' && str_contains($path, '/logout')) {
    $authController->logout($userId);
    exit;
}


/* GET /profile */

if ($method === 'GET' && str_contains($path, '/profile')) {
    $userController->profile($userId, $tenantId);
    exit;
}


/* PUT /profile */

if ($method === 'PUT' && str_contains($path, '/profile')) {
    $data = getEncryptedData();
    $userController->updateProfile($data, $userId, $tenantId);
    exit;
}


/* USER MANAGEMENT */

/* POST /users */

if ($method === 'POST' && preg_match('#/users/?$#', $path)) {
    RoleMiddleware::handle($payload, ['Admin'], $tenantPdo);

    $data = getEncryptedData();
    $userController->create($data, $tenantId);
    exit;
}


/* GET /users */

if ($method === 'GET' && preg_match('#/users/?$#', $path)) {
    RoleMiddleware::handle($payload, ['Admin'], $tenantPdo);

    $userController->index($tenantId);
    exit;
}


/* POST /users/{id}/roles */

if ($method === 'POST' && preg_match('#/users/(\d+)/roles/?$#', $path, $matches)) {
    RoleMiddleware::handle($payload, ['Admin'], $tenantPdo);

    $data = getEncryptedData();
    $userController->assignRole(
        (int)$matches[1],
        $data,
        $tenantId
    );

    exit;
}


/* DELETE /users/{id}/roles/{role} */

if ($method === 'DELETE' && preg_match('#/users/(\d+)/roles/([^/]+)/?$#', $path, $matches)) {
    RoleMiddleware::handle($payload, ['Admin'], $tenantPdo);

    $role = urldecode($matches[2]);

    $userController->removeRole(
        (int)$matches[1],
        $role,
        $tenantId
    );

    exit;
}


/* PUT /users/{id} */

if ($method === 'PUT' && preg_match('#/users/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle($payload, ['Admin'], $tenantPdo);

    $data = getEncryptedData();

    $userController->update(
        (int)$matches[1],
        $data,
        $tenantId
    );

    exit;
}


/* DELETE /users/{id} */

if ($method === 'DELETE' && preg_match('#/users/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle($payload, ['Admin'], $tenantPdo);

    $userController->delete(
        (int)$matches[1],
        $tenantId
    );

    exit;
}


/* GET /users/{id} */

if ($method === 'GET' && preg_match('#/users/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle($payload, ['Admin'], $tenantPdo);

    $userController->show(
        (int)$matches[1],
        $tenantId
    );

    exit;
}


/* PATIENT MANAGEMENT */

/* GET /patients */

if ($method === 'GET' && preg_match('#/patients/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    $patientController->index($tenantId);
    exit;
}


/* POST /patients */

if ($method === 'POST' && preg_match('#/patients/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    $data = getEncryptedData();
    $patientController->create($data, $tenantId);
    exit;
}


/* GET /patients/{id} */

if ($method === 'GET' && preg_match('#/patients/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    $patientController->show(
        (int)$matches[1],
        $tenantId
    );

    exit;
}


/* PUT /patients/{id} */

if ($method === 'PUT' && preg_match('#/patients/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    $data = getEncryptedData();

    $patientController->update(
        (int)$matches[1],
        $data,
        $tenantId
    );

    exit;
}


/* DELETE /patients/{id} */

if ($method === 'DELETE' && preg_match('#/patients/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    $patientController->delete(
        (int)$matches[1],
        $tenantId
    );

    exit;
}


/* APPOINTMENT MANAGEMENT */

/* GET /appointments */

if ($method === 'GET' && preg_match('#/appointments/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse', 'Patient'],
        $tenantPdo
    );

    $appointmentController->index($tenantId);
    exit;
}


/* POST /appointments */

if ($method === 'POST' && preg_match('#/appointments/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    $data = getEncryptedData();
    $appointmentController->create($data, $tenantId);
    exit;
}


/* GET /appointments/{id} */

if ($method === 'GET' && preg_match('#/appointments/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse', 'Patient'],
        $tenantPdo
    );

    $appointmentController->show(
        (int)$matches[1],
        $tenantId
    );

    exit;
}


/* PUT /appointments/{id} */

if ($method === 'PUT' && preg_match('#/appointments/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    $data = getEncryptedData();

    $appointmentController->update(
        (int)$matches[1],
        $data,
        $tenantId
    );

    exit;
}


/* PATCH /appointments/{id}/status */

if ($method === 'PATCH' && preg_match('#/appointments/(\d+)/status/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    $data = getEncryptedData();

    $appointmentController->updateStatus(
        (int)$matches[1],
        $data,
        $tenantId
    );

    exit;
}


/* PUT /appointments/{id}/cancel */

if ($method === 'PUT' && preg_match('#/appointments/(\d+)/cancel/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    $appointmentController->cancel(
        (int)$matches[1],
        $tenantId
    );

    exit;
}


/* CALENDAR */

/* GET /calendar/day */

if ($method === 'GET' && str_contains($path, '/calendar/day')) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    $providerId = isset($_GET['provider_id'])
        ? (int)$_GET['provider_id']
        : null;

    $calendarController->dayView(
        $tenantId,
        $providerId
    );

    exit;
}


/* GET /calendar/range */

if ($method === 'GET' && str_contains($path, '/calendar/range')) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    $providerId = isset($_GET['provider_id'])
        ? (int)$_GET['provider_id']
        : null;

    $calendarController->rangeView(
        $tenantId,
        $providerId
    );

    exit;
}


/* GET /calendar/upcoming */

if ($method === 'GET' && str_contains($path, '/calendar/upcoming')) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    $providerId = isset($_GET['provider_id'])
        ? (int)$_GET['provider_id']
        : null;

    $calendarController->upcoming(
        $tenantId,
        $providerId
    );

    exit;
}


/* GET /calendar/appointments/{id}/tooltip */

if (
    $method === 'GET' &&
    preg_match(
        '#/calendar/appointments/(\d+)/tooltip/?$#',
        $path,
        $matches
    )
) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    $calendarController->tooltip(
        $tenantId,
        (int)$matches[1]
    );

    exit;
}


/* Route not found */

Response::error('Route not found', 404);