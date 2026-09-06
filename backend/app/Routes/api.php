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

require_once __DIR__ . '/../Repositories/PrescriptionRepository.php';
require_once __DIR__ . '/../Services/PrescriptionService.php';
require_once __DIR__ . '/../Controllers/PrescriptionController.php';

require_once __DIR__ . '/../Controllers/DashboardController.php';

require_once __DIR__ . '/../Controllers/NoteController.php';

require_once __DIR__ . '/../Controllers/BillingController.php';

require_once __DIR__ . '/../Controllers/StaffController.php';

global $pdo, $masterPdo;

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptName !== '/' && $scriptName !== '\\') {
    $path = str_replace($scriptName, '', $path);
}
$path = '/' . trim($path, '/');

if (isset($_GET['debug'])) {
    echo json_encode([
        'request_uri' => $_SERVER['REQUEST_URI'],
        'script_name' => $_SERVER['SCRIPT_NAME'],
        'path' => $path
    ]);
    exit;
}

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

// if ($method === 'GET' && str_contains($path, '/csrf-token')) {
//     $token = CSRF::generate();
//     $_SESSION['csrf_token'] = $token;

//     echo json_encode([
//         'status' => true,
//         'message' => 'CSRF token generated',
//         'data' => ['csrf_token' => $token]
//     ]);

//     exit;
// }


/* Public routes */

// $isPublicRoute =
//     str_contains($path, '/tenant/register') ||
//     str_contains($path, '/register') ||
//     str_contains($path, '/login') ||
//     str_contains($path, '/refresh') ||
//     str_contains($path, '/prescriptions');


/* CSRF */

// if (!$isPublicRoute) {
//     CsrfMiddleware::handle();
// }


$tenantPdo = new PDO(
    "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=heal_tenant_1;charset=utf8mb4",
    $_ENV['DB_USER'],
    $_ENV['DB_PASSWORD']
);
$tenantPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$tenantPdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);




/* POST /tenant/register */

if ($method === 'POST' && preg_match('#/tenant/register/?$#', $path)) {
    // $data = getEncryptedData();
$data = json_decode(
    file_get_contents('php://input'),
    true
);
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

// $jwtSecret = $_ENV['JWT_SECRET'];
// $payload = AuthMiddleware::handle($jwtSecret);

// $userId = (int)$payload['user_id'];
// $tenantId = (int)$payload['tenant_id'];

// TenantMiddleware::validate(
//     $tenantId,
//     (int)$payload['tenant_id']
// );

// $tenantResolver = new TenantResolver($masterPdo);
// $tenant = $tenantResolver->resolveById($tenantId);
// $tenantPdo = $tenantResolver->connect($tenant);

/* Authenticate protected request */

// if (!$isPublicRoute) {
//     $jwtSecret = $_ENV['JWT_SECRET'];
//     $payload = AuthMiddleware::handle($jwtSecret);
//     $userId = (int)$payload['user_id'];
//     $tenantId = (int)$payload['tenant_id'];
//     TenantMiddleware::validate(
//         $tenantId,
//         (int)$payload['tenant_id']
//     );
//     $tenantResolver = new TenantResolver($masterPdo);
//     $tenant = $tenantResolver->resolveById($tenantId);
//     $tenantPdo = $tenantResolver->connect($tenant);
// }

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


// ========================================
// PRESCRIPTION ROUTES
// ========================================

$prescriptionPdo = new PDO(
    "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=heal_tenant_1;charset=utf8mb4",
    $_ENV['DB_USER'],
    $_ENV['DB_PASSWORD']
);
$prescriptionPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$prescriptionPdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

if ($method === 'POST' && preg_match('#/prescriptions/?$#', $path)) {
    try {
        $controller = new PrescriptionController($prescriptionPdo);
        $result = $controller->create();
        Response::success(
            ['prescription_id' => $result['prescription_id']],
            $result['message'],
            201
        );
    } catch (Exception $e) {
        Response::error($e->getMessage(), 400);
    }
    exit;
}

if ($method === 'GET' && preg_match('#/prescriptions/?$#', $path)) {
    try {
        $controller = new PrescriptionController($prescriptionPdo);
        $result = $controller->getAll();
        Response::success(
            $result['data'],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error($e->getMessage(), 400);
    }
    exit;
}

if ($method === 'GET' && preg_match('#/prescriptions/(\d+)/?$#', $path, $matches)) {
    try {
        $controller = new PrescriptionController($prescriptionPdo);
        $result = $controller->getById((int)$matches[1]);
        Response::success(
            $result['data'],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error($e->getMessage(), 404);
    }
    exit;
}

if ($method === 'PUT' && preg_match('#/prescriptions/(\d+)/?$#', $path, $matches)) {
    try {
        $controller = new PrescriptionController($prescriptionPdo);
        $result = $controller->update((int)$matches[1]);
        Response::success(
            [],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error($e->getMessage(), 400);
    }
    exit;
}

if ($method === 'DELETE' && preg_match('#/prescriptions/(\d+)/?$#', $path, $matches)) {
    try {
        $controller = new PrescriptionController($prescriptionPdo);
        $result = $controller->delete((int)$matches[1]);
        Response::success(
            [],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error($e->getMessage(), 404);
    }
    exit;
}

if ($method === 'PATCH' && preg_match('#/prescriptions/(\d+)/status/?$#', $path, $matches)) {
    try {
        $controller = new PrescriptionController($prescriptionPdo);
        $result = $controller->updateStatus((int)$matches[1]);
        Response::success(
            [],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error($e->getMessage(), 400);
    }
    exit;
}


// ========================================
// NOTE ROUTES
// ========================================

$notePdo = new PDO(
    "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=heal_tenant_1;charset=utf8mb4",
    $_ENV['DB_USER'],
    $_ENV['DB_PASSWORD']
);
$notePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$notePdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// ========================================
// Create Note
// ========================================

if ($method === 'POST' && preg_match('#^/notes/?$#', $path)) {
    try {
        $controller = new NoteController($notePdo);
        $result = $controller->create();
        Response::success(
            $result,
            $result['message'],
            201
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            400
        );
    }
    exit;
}

// ========================================
// Get All Notes
// ========================================

if ($method === 'GET' && preg_match('#^/notes/?$#', $path)) {
    try {
        $controller = new NoteController($notePdo);
        $result = $controller->getAll();
        Response::success(
            $result['data'],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            400
        );
    }
    exit;
}

// ========================================
// Get Note By ID
// ========================================

if ($method === 'GET' && preg_match('#^/notes/(\d+)/?$#', $path, $matches)) {
    try {
        $controller = new NoteController($notePdo);
        $result = $controller->getById(
            (int)$matches[1]
        );
        Response::success(
            $result['data'],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            404
        );
    }
    exit;
}

// ========================================
// Get Notes By Appointment ID
// ========================================

if ($method === 'GET' && preg_match('#^/appointments/(\d+)/notes/?$#', $path, $matches)) {
    try {
        $controller = new NoteController($notePdo);
        $result = $controller->getByAppointmentId(
            (int)$matches[1]
        );
        Response::success(
            $result['data'],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            400
        );
    }
    exit;
}

// ========================================
// Update Note
// ========================================

if ($method === 'PUT' && preg_match('#^/notes/(\d+)/?$#', $path, $matches)) {
    try {
        $controller = new NoteController($notePdo);
        $result = $controller->update(
            (int)$matches[1]
        );
        Response::success(
            [],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            400
        );
    }
    exit;
}

// ========================================
// Delete Note
// ========================================

if ($method === 'DELETE' && preg_match('#^/notes/(\d+)/?$#', $path, $matches)) {
    try {
        $controller = new NoteController($notePdo);
        $result = $controller->delete(
            (int)$matches[1]
        );
        Response::success(
            [],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            404
        );
    }
    exit;
}


// ========================================
// DASHBOARD ROUTES
// ========================================

$dashboardPdo = new PDO(
    "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=heal_tenant_1;charset=utf8mb4",
    $_ENV['DB_USER'],
    $_ENV['DB_PASSWORD']
);
$dashboardPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dashboardPdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Get Dashboard Data
if ($method === 'GET' && preg_match('#^/dashboard/?$#', $path)) {
    try {
        $controller = new DashboardController($dashboardPdo);
        $result = $controller->getDashboard();
        Response::success(
            $result['data'],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            400
        );
    }
    exit;
}


// ========================================
// BILLING ROUTES
// ========================================
$billingPdo = new PDO(
    "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=heal_tenant_1;charset=utf8mb4",
    $_ENV['DB_USER'],
    $_ENV['DB_PASSWORD']
);
$billingPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$billingPdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
// Create Invoice
if ($method === 'POST' && preg_match('#^/billing/?$#', $path)) {
    try {
        $controller = new BillingController($billingPdo);
        $result = $controller->create();
        Response::success(
            $result,
            $result['message'],
            201
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            400
        );
    }
    exit;
}
// Get All Invoices
if ($method === 'GET' && preg_match('#^/billing/?$#', $path)) {
    try {
        $controller = new BillingController($billingPdo);
        $result = $controller->getAll();
        Response::success(
            $result['data'],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            400
        );
    }
    exit;
}
// Get Invoice By ID
if ($method === 'GET' && preg_match('#^/billing/(\d+)/?$#', $path, $matches)) {
    try {
        $controller = new BillingController($billingPdo);
        $result = $controller->getById(
            (int)$matches[1]
        );
        Response::success(
            $result['data'],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            404
        );
    }
    exit;
}

// Get Payment Summary
if ($method === 'GET' && preg_match('#^/billing/summary/?$#', $path)) {
    try {
        $controller = new BillingController($billingPdo);
        $result = $controller->getPaymentSummary();
        Response::success(
            $result['data'],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            400
        );
    }
    exit;
}


// Update Invoice
if ($method === 'PUT' && preg_match('#^/billing/(\d+)/?$#', $path, $matches)) {
    try {
        $controller = new BillingController($billingPdo);
        $result = $controller->update(
            (int)$matches[1]
        );
        Response::success(
            [],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            400
        );
    }
    exit;
}
// Delete Invoice
if ($method === 'DELETE' && preg_match('#^/billing/(\d+)/?$#', $path, $matches)) {
    try {
        $controller = new BillingController($billingPdo);
        $result = $controller->delete(
            (int)$matches[1]
        );
        Response::success(
            [],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            404
        );
    }
    exit;
}
// Update Payment Status
if ($method === 'PATCH' && preg_match('#^/billing/(\d+)/status/?$#', $path, $matches)) {
    try {
        $controller = new BillingController($billingPdo);
        $result = $controller->updatePaymentStatus(
            (int)$matches[1]
        );
        Response::success(
            [],
            $result['message'],
            200
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            400
        );
    }
    exit;
}



// ========================================
// STAFF ROUTES
// ========================================


// Create Staff
if ($method === 'POST' && preg_match('#^/staff/?$#', $path)) {

    try {

        $controller = new StaffController($prescriptionPdo);

        $result = $controller->create();

        Response::success(
            $result,
            $result['message'],
            201
        );

    } catch (Exception $e) {

        Response::error(
            $e->getMessage(),
            400
        );
    }

    exit;
}


// ========================================
// Get All Staff
// ========================================

if ($method === 'GET' && preg_match('#^/staff/?$#', $path)) {

    try {

        $controller = new StaffController($prescriptionPdo);

        $result = $controller->getAll();

        Response::success(
            $result['data'],
            $result['message'],
            200
        );

    } catch (Exception $e) {

        Response::error(
            $e->getMessage(),
            400
        );
    }

    exit;
}


// ========================================
// Get Staff By ID
// ========================================

if (
    $method === 'GET' &&
    preg_match('#^/staff/(\d+)/?$#', $path, $matches)
) {

    try {

        $controller = new StaffController($prescriptionPdo);

        $result = $controller->getById(
            (int)$matches[1]
        );

        Response::success(
            $result['data'],
            $result['message'],
            200
        );

    } catch (Exception $e) {

        Response::error(
            $e->getMessage(),
            404
        );
    }

    exit;
}


// ========================================
// Update Staff
// ========================================

if (
    $method === 'PUT' &&
    preg_match('#^/staff/(\d+)/?$#', $path, $matches)
) {

    try {

        $controller = new StaffController($prescriptionPdo);

        $result = $controller->update(
            (int)$matches[1]
        );

        Response::success(
            [],
            $result['message'],
            200
        );

    } catch (Exception $e) {

        Response::error(
            $e->getMessage(),
            400
        );
    }

    exit;
}


// ========================================
// Delete Staff
// ========================================

if (
    $method === 'DELETE' &&
    preg_match('#^/staff/(\d+)/?$#', $path, $matches)
) {

    try {

        $controller = new StaffController($prescriptionPdo);

        $result = $controller->delete(
            (int)$matches[1]
        );

        Response::success(
            [],
            $result['message'],
            200
        );

    } catch (Exception $e) {

        Response::error(
            $e->getMessage(),
            404
        );
    }

    exit;
}


// ========================================
// Update Staff Status
// ========================================

if (
    $method === 'PATCH' &&
    preg_match(
        '#^/staff/(\d+)/status/?$#',
        $path,
        $matches
    )
) {

    try {

        $controller = new StaffController($prescriptionPdo);

        $result = $controller->updateStatus(
            (int)$matches[1]
        );

        Response::success(
            [],
            $result['message'],
            200
        );

    } catch (Exception $e) {

        Response::error(
            $e->getMessage(),
            400
        );
    }

    exit;
}



/* Route not found */

Response::error('Route not found', 404);