<?php

require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Controllers/MedicineController.php';
require_once __DIR__ . '/../Controllers/PrescriptionController.php';

$method = $_SERVER['REQUEST_METHOD'];
// $path = $_SERVER['REQUEST_URI'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$authController = new AuthController();
$medicineController = new MedicineController();
$prescriptionController = new PrescriptionController();

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



// ==========================================================
// MEDICINE ROUTES
// ==========================================================


// ----------------------------------------
// CREATE MEDICINE
// POST /medicines
// ----------------------------------------

if ($method === 'POST' && str_contains($path, '/medicines')) {

    try {

        $result = $medicineController->create();

        echo json_encode([
            'status' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);

    } catch (Exception $e) {

        http_response_code(400);

        echo json_encode([
            'status' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}


// ----------------------------------------
// GET ALL MEDICINES
// GET /medicines?tenant_id=1
// ----------------------------------------

if ($method === 'GET' && preg_match('#/medicines/?$#', $path)) {

    try {

        $result = $medicineController->getAll();

        echo json_encode([
            'status' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);

    } catch (Exception $e) {

        http_response_code(400);

        echo json_encode([
            'status' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}


// ----------------------------------------
// GET SINGLE MEDICINE
// GET /medicines/1?tenant_id=1
// ----------------------------------------

if (
    $method === 'GET' &&
    preg_match('#/medicines/(\d+)/?$#', $path, $matches)
) {

    try {

        $medicineId = (int) $matches[1];

        $result = $medicineController->getById($medicineId);

        echo json_encode([
            'status' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);

    } catch (Exception $e) {

        http_response_code(404);

        echo json_encode([
            'status' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}


// ----------------------------------------
// UPDATE MEDICINE
// PUT /medicines/1
// ----------------------------------------

if (
    $method === 'PUT' &&
    preg_match('#/medicines/(\d+)/?$#', $path, $matches)
) {

    try {

        $medicineId = (int) $matches[1];

        $result = $medicineController->update($medicineId);

        echo json_encode([
            'status' => true,
            'message' => $result['message']
        ]);

    } catch (Exception $e) {

        http_response_code(400);

        echo json_encode([
            'status' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}


// ----------------------------------------
// DELETE MEDICINE
// DELETE /medicines/1?tenant_id=1
// ----------------------------------------

if (
    $method === 'DELETE' &&
    preg_match('#/medicines/(\d+)/?$#', $path, $matches)
) {

    try {

        $medicineId = (int) $matches[1];

        $result = $medicineController->delete($medicineId);

        echo json_encode([
            'status' => true,
            'message' => $result['message']
        ]);

    } catch (Exception $e) {

        http_response_code(404);

        echo json_encode([
            'status' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}






// ========================================
// CREATE PRESCRIPTION
// ========================================

if (
    $method === 'POST' &&
    str_contains($path, '/prescriptions')
) {

    // Temporary user data for testing
    $user = [
        'tenant_id' => 1
    ];

    $response = $prescriptionController->create($user);

    echo json_encode([
        'status' => true,
        'data' => $response
    ]);

    exit;
}


// ========================================
// GET ALL PRESCRIPTIONS
// GET /prescriptions
// ========================================

if (
    $method === 'GET' &&
    preg_match('#/prescriptions/?$#', $path)
) {

    try {

        // Temporary user for testing
        $user = [
            'tenant_id' => 1
        ];

        $response =
            $prescriptionController->getAll($user);

        echo json_encode([
            'status' => true,
            'message' => $response['message'],
            'data' => $response['data']
        ]);

    } catch (Exception $e) {

        http_response_code(400);

        echo json_encode([
            'status' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}

// ========================================
// GET SINGLE PRESCRIPTION
// GET /prescriptions/1
// ========================================

if (
    $method === 'GET' &&
    preg_match(
        '#/prescriptions/(\d+)/?$#',
        $path,
        $matches
    )
) {

    try {

        $prescriptionId = (int) $matches[1];

        // Temporary user for testing
        $user = [
            'tenant_id' => 1
        ];

        $response =
            $prescriptionController->getById(
                $prescriptionId,
                $user
            );

        echo json_encode([
            'status' => true,
            'message' => $response['message'],
            'data' => $response['data']
        ]);

    } catch (Exception $e) {

        http_response_code(404);

        echo json_encode([
            'status' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}

// ========================================
// UPDATE PRESCRIPTION
// PUT /prescriptions/{id}
// ========================================

if (
    $method === 'PUT' &&
    preg_match('#/prescriptions/(\d+)/?$#', $path, $matches)
) {

    try {

        $prescriptionId = (int) $matches[1];

        // Temporary user data for testing
        $user = [
            'tenant_id' => 1
        ];

        $response = $prescriptionController->update(
            $prescriptionId,
            $user
        );

        echo json_encode([
            'status' => true,
            'message' => $response['message']
        ]);

    } catch (Exception $e) {

        http_response_code(400);

        echo json_encode([
            'status' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}

// ========================================
// DELETE PRESCRIPTION
// DELETE /prescriptions/{id}
// ========================================

if (
    $method === 'DELETE' &&
    preg_match('#/prescriptions/(\d+)/?$#', $path, $matches)
) {

    try {

        $prescriptionId = (int) $matches[1];

        // Temporary user data for testing
        $user = [
            'tenant_id' => 1
        ];

        $response = $prescriptionController->delete(
            $prescriptionId,
            $user
        );

        echo json_encode([
            'status' => true,
            'message' => $response['message']
        ]);

    } catch (Exception $e) {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'message' => $e->getMessage()]);
    }

    exit;
}

// ========================================
// UPDATE PRESCRIPTION STATUS
// PUT /prescriptions/1/status
// ========================================

if (
    $method === 'PUT' &&
    preg_match(
        '#/prescriptions/(\d+)/status/?$#',
        $path,
        $matches
    )
) {

    try {

        $prescriptionId = (int) $matches[1];

        // Temporary user for testing
        $user = [
            'tenant_id' => 1
        ];

        $response =
            $prescriptionController->updateStatus(
                $prescriptionId,
                $user
            );

        echo json_encode([
            'status' => true,
            'message' => $response['message']
        ]);

    } catch (Exception $e) {

        http_response_code(400);

        echo json_encode([
            'status' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}

// --------------------------------------------       ROUTE NOT FOUND        -------------------------------------------------------


http_response_code(404);

echo json_encode([
    'status' => false,
    'message' => 'Route not found'
]);