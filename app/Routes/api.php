<?php

require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Controllers/UserController.php';
require_once __DIR__ . '/../Controllers/PatientController.php';
require_once __DIR__ . '/../Controllers/AppointmentController.php';
require_once __DIR__ . '/../Controllers/CalendarController.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

$authController = new AuthController();
$userController = new UserController();
$patientController = new PatientController();
$appointmentController = new AppointmentController();
$calendarController = new CalendarController();


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

    $jwtSecret = $_ENV['JWT_SECRET'];

    $payload = AuthMiddleware::handle($jwtSecret);

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


// ========================================== MODULE 2 - USER MANAGEMENT ==========================================

if ($method === 'GET' && str_contains($path, '/profile')) {

    $userController->profile();

    exit;
}

if ($method === 'PUT' && str_contains($path, '/profile')) {

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    $userController->updateProfile($data);

    exit;
}

if ($method === 'POST' && str_contains($path, '/users')) {

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    $userController->create($data);

    exit;
}

if ($method === 'GET' && str_contains($path, '/users')) {

    $userController->index();

    exit;
}


// ========================================== MODULE 3 - PATIENT MANAGEMENT ==========================================

if ($method === 'POST' && str_contains($path, '/patients')) {

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    $patientController->create($data);

    exit;
}

if ($method === 'GET' && str_contains($path, '/patients')) {

    $patientController->index();

    exit;
}

if ($method === 'GET' && preg_match('#/patients/(\d+)#', $path, $matches)) {

    $patientController->show((int) $matches[1]);

    exit;
}

if ($method === 'PUT' && preg_match('#/patients/(\d+)#', $path, $matches)) {

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    $patientController->update(
        (int) $matches[1],
        $data
    );

    exit;
}

if ($method === 'DELETE' && preg_match('#/patients/(\d+)#', $path, $matches)) {

    $patientController->delete((int) $matches[1]);

    exit;
}


// ========================================== MODULE 4 - APPOINTMENT MANAGEMENT ==========================================

if ($method === 'POST' && str_contains($path, '/appointments')) {

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    $appointmentController->create($data);

    exit;
}

if ($method === 'GET' && str_contains($path, '/appointments')) {

    $appointmentController->index();

    exit;
}

if ($method === 'GET' && preg_match('#/appointments/(\d+)#', $path, $matches)) {

    $appointmentController->show((int) $matches[1]);

    exit;
}

if ($method === 'PUT' && preg_match('#/appointments/(\d+)#', $path, $matches)) {

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    $appointmentController->update(
        (int) $matches[1],
        $data
    );

    exit;
}

if ($method === 'PATCH' && preg_match('#/appointments/(\d+)/status#', $path, $matches)) {

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    $appointmentController->updateStatus(
        (int) $matches[1],
        $data
    );

    exit;
}

if ($method === 'PUT' && preg_match('#/appointments/(\d+)/cancel#', $path, $matches)) {

    $appointmentController->cancel((int) $matches[1]);

    exit;
}


// ========================================== MODULE 10 - CALENDAR ==========================================

if ($method === 'GET' && str_contains($path, '/calendar/day')) {

    $calendarController->dayView();

    exit;
}

if ($method === 'GET' && str_contains($path, '/calendar/range')) {

    $calendarController->rangeView();

    exit;
}

if ($method === 'GET' && str_contains($path, '/calendar/upcoming')) {

    $calendarController->upcoming();

    exit;
}

if ($method === 'GET' && preg_match('#/calendar/appointments/(\d+)/tooltip#', $path, $matches)) {

    $calendarController->tooltip((int) $matches[1]);

    exit;
}


// --------------------------------------------       ROUTE NOT FOUND        -------------------------------------------------------

http_response_code(404);

echo json_encode([
    'status' => false,
    'message' => 'Route not found'
]);