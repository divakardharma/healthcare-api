<?php

require_once __DIR__ . '/../Services/StaffService.php';


class StaffController
{
    private StaffService $staffService;


    public function __construct(PDO $pdo)
    {
        $this->staffService = new StaffService($pdo);
    }


    // ========================================
    // Create Staff
    // ========================================

    public function create(): array
    {
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($input)) {
            throw new Exception('Invalid JSON request');
        }

        $userId = $input['user_id'] ?? 0;
        $roleId = $input['role_id'] ?? 0;
        $status = $input['status'] ?? 'Active';


        $staffId = $this->staffService->createStaff(
            (int)$userId,
            (int)$roleId,
            $status
        );


        return [
            'message' => 'Staff created successfully',
            'staff_id' => $staffId
        ];
    }


    // ========================================
    // Get All Staff
    // ========================================

    public function getAll(): array
    {
        $staff = $this->staffService->getAllStaff();

        return [
            'message' => 'Staff fetched successfully',
            'data' => $staff
        ];
    }


    // ========================================
    // Get Staff By ID
    // ========================================

    public function getById(
        int $staffId
    ): array {

        $staff = $this->staffService->getStaffById(
            $staffId
        );

        return [
            'message' => 'Staff fetched successfully',
            'data' => $staff
        ];
    }


    // ========================================
    // Update Staff
    // ========================================

    public function update(
        int $staffId
    ): array {

        $input = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($input)) {
            throw new Exception('Invalid JSON request');
        }

        $userId = $input['user_id'] ?? 0;
        $roleId = $input['role_id'] ?? 0;
        $status = $input['status'] ?? 'Active';


        $this->staffService->updateStaff(
            $staffId,
            (int)$userId,
            (int)$roleId,
            $status
        );


        return [
            'message' => 'Staff updated successfully'
        ];
    }


    // ========================================
    // Delete Staff
    // ========================================

    public function delete(
        int $staffId
    ): array {

        $this->staffService->deleteStaff(
            $staffId
        );

        return [
            'message' => 'Staff deleted successfully'
        ];
    }


    // ========================================
    // Update Staff Status
    // ========================================

    public function updateStatus(
        int $staffId
    ): array {

        $input = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($input)) {
            throw new Exception('Invalid JSON request');
        }

        $status = $input['status'] ?? '';


        $this->staffService->updateStatus(
            $staffId,
            $status
        );


        return [
            'message' => 'Staff status updated successfully'
        ];
    }
}