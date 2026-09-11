<?php

require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Controllers/UserController.php';
require_once __DIR__ . '/../Controllers/PatientController.php';
require_once __DIR__ . '/../Controllers/AppointmentController.php';
require_once __DIR__ . '/../Controllers/CalendarController.php';
require_once __DIR__ . '/../Controllers/TenantController.php';
require_once __DIR__ . '/../Controllers/PrescriptionController.php';
require_once __DIR__ . '/../Controllers/DashboardController.php';
require_once __DIR__ . '/../Controllers/NoteController.php';
require_once __DIR__ . '/../Controllers/BillingController.php';
require_once __DIR__ . '/../Controllers/StaffController.php';

require_once __DIR__ . '/../Services/UserService.php';
require_once __DIR__ . '/../Services/PatientService.php';
require_once __DIR__ . '/../Services/AppointmentService.php';
require_once __DIR__ . '/../Services/CalendarService.php';
require_once __DIR__ . '/../Services/TenantService.php';
require_once __DIR__ . '/../Services/TenantProvisioningService.php';
require_once __DIR__ . '/../Services/TenantResolver.php';
require_once __DIR__ . '/../Services/PrescriptionService.php';

require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/RoleRepository.php';
require_once __DIR__ . '/../Repositories/PatientRepository.php';
require_once __DIR__ . '/../Repositories/AppointmentRepository.php';
require_once __DIR__ . '/../Repositories/PrescriptionRepository.php';
require_once __DIR__ . '/../Repositories/StaffRepository.php';

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
$scriptName = dirname($_SERVER['SCRIPT_NAME']);

if ($scriptName !== '/' && $scriptName !== '\\') {
    $path = str_replace($scriptName, '', $path);
}

$path = '/' . trim($path, '/');

/*------------------------------------------ Read + decrypt request payload------------------------------------ */

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

// ---------------------------------------------------GET /csrf-token---------------------------------------------

if ($method === 'GET' && str_contains($path, '/csrf-token')) {
    $token = CSRF::generate();
    $_SESSION['csrf_token'] = $token;

 Response::success( ['csrf_token' => $token], 'CSRF token generated');
    exit;
}

/* -------------------------------------------------Public routes --------------------------------------------------------------*/

$isPublicRoute =
    str_contains($path, '/tenant/register') ||
    // str_contains($path, '/register') ||
    str_contains($path, '/login') ||
    str_contains($path, '/refresh') ;

/* CSRF */

// if (
//     !$isPublicRoute ||
//     str_contains($path, '/login') ||
//     str_contains($path, '/tenant/register') ||
//     str_contains($path, '/refresh') 
// ) {
    CsrfMiddleware::handle();
// }

//------------------------------------------------------ POST /tenant/register---------------------------------------------------

if ($method === 'POST' && preg_match('#^/tenant/register/?$#', $path)) {
    $data = getEncryptedData();

    $provisioningService = new TenantProvisioningService($masterPdo);
    $tenantService = new TenantService($masterPdo, $provisioningService);
    $tenantController = new TenantController($tenantService);

    $tenantController->register($data);
    exit;
}

/* ---------------------------------------------------POST /login------------------------------------------------------ */

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

/*------------------------------------------------- POST /refresh ------------------------------------------------------------*/

if ($method === 'POST' && str_contains($path, '/refresh')) {
    $data = getEncryptedData();

    $subdomain = strtolower(trim($data['subdomain'] ?? ''));

    if ($subdomain === '') {
        Response::error('Subdomain is required', 422);
    }

    $data['refresh_token'] = $_COOKIE['refresh_token'] ?? '';

    if (empty($data['refresh_token'])) {
        Response::error('Refresh token cookie is missing', 422);
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

/*--------------------------------------------- Authenticate protected request----------------------------------------- */

if (!$isPublicRoute) {
    $jwtSecret = $_ENV['JWT_SECRET'];

    $payload = AuthMiddleware::handle($jwtSecret);
    $userId = (int)$payload['user_id'];
    $tenantId = (int)$payload['tenant_id'];

    // TenantMiddleware::validate(
    //     $tenantId,
    //     (int)$payload['tenant_id']
    // );

    $tenantResolver = new TenantResolver($masterPdo);
    $tenant = $tenantResolver->resolveById($tenantId);
    $tenantPdo = $tenantResolver->connect($tenant);
}

/* Controllers */

$authController = new AuthController($tenantPdo);

$userRepository = new UserRepository($tenantPdo);
$roleRepository = new RoleRepository($tenantPdo);
$staffRepository = new StaffRepository($tenantPdo);
$patientRepository = new PatientRepository($tenantPdo);
$appointmentRepository = new AppointmentRepository($tenantPdo);

$userService = new UserService($userRepository, $roleRepository, $staffRepository);
$patientService = new PatientService($patientRepository);
$appointmentService = new AppointmentService($appointmentRepository);
$calendarService = new CalendarService($appointmentRepository);

$userController = new UserController($userService);
$patientController = new PatientController($patientService);
$appointmentController = new AppointmentController($appointmentService);
$calendarController = new CalendarController($calendarService);

/*--------------     POST/change-password */

if ($method === 'POST' && str_contains($path, '/change-password')) {
    $data = getEncryptedData();
    $authController->changePassword($data, $userId);
    exit;
}

/*---------------     POST /logout */

if ($method === 'POST' && str_contains($path, '/logout')) {
    $authController->logout($userId);
    exit;
}

/*-----------------    GET /profile */    

if ($method === 'GET' && str_contains($path, '/profile')) {
    $userController->profile($userId, $tenantId);
    exit;
}

/*-----------------    PUT /profile */

if ($method === 'PUT' && str_contains($path, '/profile')) {
    $data = getEncryptedData();
    $userController->updateProfile($data, $userId, $tenantId);
    exit;
}

/*-------------------------------------       USER MANAGEMENT       -----------------------------------------------------------*/

if ($method === 'POST' && preg_match('#/users/?$#', $path)) {
    RoleMiddleware::handle($payload, ['Admin'], $tenantPdo);

    $data = getEncryptedData();
    $userController->create($data, $tenantId);
    exit;
}

if ($method === 'GET' && preg_match('#/users/?$#', $path)) {
    RoleMiddleware::handle($payload, ['Admin'], $tenantPdo);

    $userController->index($tenantId);
    exit;
}

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

if ($method === 'DELETE' && preg_match('#/users/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle($payload, ['Admin'], $tenantPdo);

    $userController->delete(
        (int)$matches[1],
        $tenantId
    );

    exit;
}

if ($method === 'GET' && preg_match('#/users/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle($payload, ['Admin'], $tenantPdo);

    $userController->show(
        (int)$matches[1],
        $tenantId
    );

    exit;
}

/*------------------------------------------------ PATIENT MANAGEMENT------------------------------------------------------ */

if ($method === 'GET' && preg_match('#/patients/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        [ 'Provider', 'Nurse'],
        $tenantPdo
    );

    $patientController->index($tenantId);
    exit;
}

if ($method === 'POST' && preg_match('#/patients/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        ['Provider', 'Nurse'],
        $tenantPdo
    );

    $data = getEncryptedData();
    $patientController->create($data, $tenantId);
    exit;
}

if ($method === 'GET' && preg_match('#/patients/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Provider', 'Nurse'],
        $tenantPdo
    );

    $patientController->show(
        (int)$matches[1],
        $tenantId
    );

    exit;
}

if ($method === 'PUT' && preg_match('#/patients/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Provider', 'Nurse'],
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

if ($method === 'DELETE' && preg_match('#/patients/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Provider', 'Nurse'],
        $tenantPdo
    );

    $patientController->delete(
        (int)$matches[1],
        $tenantId
    );

    exit;
}

/*-------------------------------------------------- APPOINTMENT MANAGEMEN----------------------------------------------------T */

if ($method === 'GET' && preg_match('#/appointments/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        ['Provider', 'Nurse'],
        $tenantPdo
    );

    $appointmentController->index($tenantId);
    exit;
}

if ($method === 'POST' && preg_match('#/appointments/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        [ 'Provider', 'Nurse'],
        $tenantPdo
    );

    $data = getEncryptedData();
    $appointmentController->create($data, $tenantId);
    exit;
}

if ($method === 'GET' && preg_match('#/appointments/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        [ 'Provider', 'Nurse'],
        $tenantPdo
    );

    $appointmentController->show(
        (int)$matches[1],
        $tenantId
    );

    exit;
}

if ($method === 'PUT' && preg_match('#/appointments/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Provider', 'Nurse'],
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

if ($method === 'PATCH' && preg_match('#/appointments/(\d+)/status/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        [ 'Provider', 'Nurse'],
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

if ($method === 'PUT' && preg_match('#/appointments/(\d+)/cancel/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        [ 'Provider', 'Nurse'],
        $tenantPdo
    );

    $appointmentController->cancel(
        (int)$matches[1],
        $tenantId
    );

    exit;
}

/* --------------------------------------------------CALENDAR------------------------------------------------------------ */

if ($method === 'GET' && str_contains($path, '/calendar/day')) {
    RoleMiddleware::handle(
        $payload,
        ['Provider', 'Nurse','Receptionist'],
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

if ($method === 'GET' && str_contains($path, '/calendar/range')) {
    RoleMiddleware::handle(
        $payload,
         ['Provider', 'Nurse','Receptionist'],
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

if ($method === 'GET' && str_contains($path, '/calendar/upcoming')) {
    RoleMiddleware::handle(
        $payload,
         ['Provider', 'Nurse','Receptionist'],
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
        ['Provider', 'Nurse','Receptionist'],
        $tenantPdo
    );

    $calendarController->tooltip(
        $tenantId,
        (int)$matches[1]
    );

    exit;
}

/*------------------------------------------------- PRESCRIPTION ROUTES----------------------------------------------------- */

// POST /prescriptions create a new prescription
if ($method === 'POST' && preg_match('#/prescriptions/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        ['Provider'],
        $tenantPdo
    );

    try {
        // Read and decrypt the encrypted data
        $data = getEncryptedData();
        
        $controller = new PrescriptionController($tenantPdo);
        $result = $controller->create($data);

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

// GET /prescriptions fetch all prescriptions
if ($method === 'GET' && preg_match('#/prescriptions/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        ['Provider', 'Pharmacist'],
        $tenantPdo
    );

    try {
        $controller = new PrescriptionController($tenantPdo);
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

// GET /prescriptions/{id} fetch a prescription by ID
if ($method === 'GET' && preg_match('#/prescriptions/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Provider', 'Pharmacist'],
        $tenantPdo
    );

    try {
        $controller = new PrescriptionController($tenantPdo);
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

// PUT /prescriptions/{id} update a prescription by ID
if ($method === 'PUT' && preg_match('#/prescriptions/(\d+)/?$#', $path, $matches)) {

    RoleMiddleware::handle(
        $payload,
        [ 'Provider'],
        $tenantPdo
    );

    try {
        $data = getEncryptedData();

        $controller = new PrescriptionController($tenantPdo);

        $result = $controller->update(
            (int)$matches[1],
            $data
        );

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

// DELETE /prescriptions/{id} delete a prescription by ID
if ($method === 'DELETE' && preg_match('#/prescriptions/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Provider'],
        $tenantPdo
    );

    try {
        $controller = new PrescriptionController($tenantPdo);
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

// PATCH /prescriptions/{id}/status update the status of a prescription by ID
if ($method === 'PATCH' && preg_match(
    '#/prescriptions/(\d+)/status/?$#',
    $path,
    $matches
)) {
    RoleMiddleware::handle(
        $payload,
        ['Pharmacist'],
        $tenantPdo
    );

    try {
        $data = getEncryptedData();

        $controller = new PrescriptionController($tenantPdo);

        $result = $controller->updateStatus(
            (int)$matches[1],
            $data
        );

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

/*------------------------------------------------ NOTE ROUTES------------------------------------------------------------- */

// POST /notes create a new note
if ($method === 'POST' && preg_match('#^/notes/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        ['Provider', 'Nurse'],
        $tenantPdo
    );

    try {
        // Decrypt request payload
        $data = getEncryptedData();

        $controller = new NoteController($tenantPdo);

        // Pass decrypted data
        $result = $controller->create($data);

        Response::success(
            $result,
            $result['message'],
            201
        );

    } catch (Exception $e) {
        Response::error($e->getMessage(), 400);
    }

    exit;
}

// GET /notes fetch all notes
if ($method === 'GET' && preg_match('#^/notes/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        [ 'Provider', 'Nurse'],
        $tenantPdo
    );

    try {
        $controller = new NoteController($tenantPdo);
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

// GET /notes/{id} fetch a note by ID
if ($method === 'GET' && preg_match('#^/notes/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        [ 'Provider', 'Nurse'],
        $tenantPdo
    );

    try {
        $controller = new NoteController($tenantPdo);
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

// GET /appointments/{id}/notes fetch notes by appointment ID
if ($method === 'GET' && preg_match('#^/appointments/(\d+)/notes/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Provider', 'Nurse'],
        $tenantPdo
    );

    try {
        $controller = new NoteController($tenantPdo);
        $result = $controller->getByAppointmentId((int)$matches[1]);

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

// PUT /notes/{id} update a note by ID
if ($method === 'PUT' && preg_match('#^/notes/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        [ 'Provider', 'Nurse'],
        $tenantPdo
    );

    try {
        // Decrypt request payload
        $data = getEncryptedData();

        $controller = new NoteController($tenantPdo);

        // Pass decrypted data
        $result = $controller->update(
            (int)$matches[1],
            $data
        );

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

// DELETE /notes/{id} delete a note by ID
if ($method === 'DELETE' && preg_match('#^/notes/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        [ 'Provider', 'Nurse'],
        $tenantPdo
    );

    try {
        $controller = new NoteController($tenantPdo);
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

/*----------------------------------------------------DASHBOARD ROUTES ----------------------------------------------------------*/

if ($method === 'GET' && preg_match('#^/dashboard/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider'],
        $tenantPdo
    );

    try {
        $controller = new DashboardController($tenantPdo);
        $result = $controller->getDashboard();

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

/* -------------------------------------------------------BILLING ROUTES-----------------------------------------------------------*/

// ========================================
// CREATE INVOICE
// POST /billing
// ========================================

if ($method === 'POST' && preg_match('#^/billing/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin','Provider'],
        
        $tenantPdo
    );

    try {
        $data = getEncryptedData();

        $controller = new BillingController($tenantPdo);
        $result = $controller->create($data);

        Response::success(
            ['billing_id' => $result['billing_id']],
            $result['message'],
            201
        );
    } catch (Exception $e) {
        Response::error($e->getMessage(), 400);
    }

    exit;
}

// ========================================
// GET ALL INVOICES
// GET /billing
// ========================================

if ($method === 'GET' && preg_match('#^/billing/?$#', $path)){
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    try {

        $controller = new BillingController($tenantPdo);

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
// PAYMENT SUMMARY
// GET /billing/summary
// ========================================

if ($method === 'GET' && preg_match('#^/billing/summary/?$#', $path)) {

    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    try {

        $controller = new BillingController($tenantPdo);

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


// GET BILLING BY ID
// GET /billing/{id}
if ($method === 'GET' && preg_match('#^/billing/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider', 'Nurse'],
        $tenantPdo
    );

    try {

        $controller = new BillingController($tenantPdo);

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
// UPDATE INVOICE
// PUT /billing/{id}
if ($method === 'PUT' && preg_match('#^/billing/(\d+)/?$#', $path, $matches)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider'],
        $tenantPdo
    );

    try {
        $data = getEncryptedData();

        $controller = new BillingController($tenantPdo);
        $result = $controller->update(
            (int)$matches[1],
            $data
        );

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
// DELETE INVOICE
// DELETE /billing/{id}
// ========================================

if ($method === 'DELETE' && preg_match('#^/billing/(\d+)/?$#', $path, $matches)) {

    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider'],
        $tenantPdo
    );

    try {

        $controller = new BillingController($tenantPdo);

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

// UPDATE PAYMENT STATUS
// PATCH /billing/{id}/status
if ($method === 'PATCH' && preg_match(
    '#^/billing/(\d+)/status/?$#',
    $path,
    $matches )) {
    RoleMiddleware::handle(
        $payload,
        ['Admin', 'Provider'],
        $tenantPdo
    );
    try {
        $data = getEncryptedData();

        $controller = new BillingController($tenantPdo);

        $result = $controller->updatePaymentStatus(
            (int)$matches[1],
            $data
        );

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

/*---------------------------------------------------- STAFF ROUTES-------------------------------------------------------- */

// ========================================
// CREATE STAFF
// POST /staff
// ========================================

if ($method === 'POST' && preg_match('#^/staff/?$#', $path)){
    RoleMiddleware::handle(
        $payload,
        ['Admin'],
        $tenantPdo);
    try {
        // Decrypt request payload
        $data = getEncryptedData();

        $controller = new StaffController($tenantPdo);

        $result = $controller->create($data);

        Response::success(
            [
                'staff_id' => $result['staff_id']
            ],
            $result['message'],
            201
        );

    } catch (Exception $e) {
        Response::error($e->getMessage(), 400);
    }

    exit;
}


// ========================================
// GET ALL STAFF
// GET /staff
// ========================================

if ($method === 'GET' && preg_match('#^/staff/?$#', $path)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin'],
        $tenantPdo
    );
    try {
        $controller = new StaffController($tenantPdo);

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


// ========================================
// GET STAFF BY ID
// GET /staff/{id}
// ========================================

if ($method === 'GET' && preg_match(
    '#^/staff/(\d+)/?$#',
    $path,
    $matches
)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin'],
        $tenantPdo
    );

    try {
        $controller = new StaffController($tenantPdo);

        $result = $controller->getById(
            (int)$matches[1]
        );

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


// ========================================
// UPDATE STAFF
// PUT /staff/{id}
// ========================================

if ($method === 'PUT' && preg_match(
    '#^/staff/(\d+)/?$#',
    $path,
    $matches
)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin'],
        $tenantPdo
    );

    try {
        // Decrypt request payload
        $data = getEncryptedData();

        $controller = new StaffController($tenantPdo);

        $result = $controller->update(
            (int)$matches[1],
            $data
        );

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
// DELETE STAFF
// DELETE /staff/{id}
// ========================================

if ($method === 'DELETE' && preg_match(
    '#^/staff/(\d+)/?$#',
    $path,
    $matches
)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin'],
        $tenantPdo
    );

    try {
        $controller = new StaffController($tenantPdo);

        $result = $controller->delete(
            (int)$matches[1]
        );

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


// ========================================
// UPDATE STAFF STATUS
// PATCH /staff/{id}/status
// ========================================

if ($method === 'PATCH' && preg_match(
    '#^/staff/(\d+)/status/?$#',
    $path,
    $matches
)) {
    RoleMiddleware::handle(
        $payload,
        ['Admin'],
        $tenantPdo
    );

    try {
        // Decrypt request payload
        $data = getEncryptedData();

        $controller = new StaffController($tenantPdo);

        $result = $controller->updateStatus(
            (int)$matches[1],
            $data
        );

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

/*------------------------------------------------------ Route not found--------------------------------------------------- */

Response::error('Route not found', 404);