<?php

require_once __DIR__ . '/../Repositories/StaffRepository.php';


class StaffService
{
    private StaffRepository $staffRepository;


    public function __construct(PDO $pdo)
    {
        $this->staffRepository = new StaffRepository($pdo);
    }


    // ========================================
    // Create Staff
    // ========================================

    public function createStaff(
        int $userId,
        int $roleId,
        string $status
    ): int {

        if ($userId <= 0) {
            throw new Exception('Valid user ID is required');
        }

        if ($roleId <= 0) {
            throw new Exception('Valid role ID is required');
        }

        $allowedStatuses = [
            'Active',
            'Inactive'
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new Exception('Invalid staff status');
        }

        return $this->staffRepository->create(
            $userId,
            $roleId,
            $status
        );
    }


    // ========================================
    // Get All Staff
    // ========================================

    public function getAllStaff(): array
    {
        return $this->staffRepository->getAll();
    }


    // ========================================
    // Get Staff By ID
    // ========================================

    public function getStaffById(
        int $staffId
    ): array {

        if ($staffId <= 0) {
            throw new Exception('Invalid staff ID');
        }

        $staff = $this->staffRepository->getById(
            $staffId
        );

        if (!$staff) {
            throw new Exception('Staff not found');
        }

        return $staff;
    }


    // ========================================
    // Update Staff
    // ========================================

    public function updateStaff(
        int $staffId,
        int $userId,
        int $roleId,
        string $status
    ): bool {

        if ($staffId <= 0) {
            throw new Exception('Invalid staff ID');
        }

        $existingStaff = $this->staffRepository->getById(
            $staffId
        );

        if (!$existingStaff) {
            throw new Exception('Staff not found');
        }

        if ($userId <= 0) {
            throw new Exception('Valid user ID is required');
        }

        if ($roleId <= 0) {
            throw new Exception('Valid role ID is required');
        }

        $allowedStatuses = [
            'Active',
            'Inactive'
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new Exception('Invalid staff status');
        }

        return $this->staffRepository->update(
            $staffId,
            $userId,
            $roleId,
            $status
        );
    }


    // ========================================
    // Delete Staff
    // ========================================

    public function deleteStaff(
        int $staffId
    ): bool {

        if ($staffId <= 0) {
            throw new Exception('Invalid staff ID');
        }

        $staff = $this->staffRepository->getById(
            $staffId
        );

        if (!$staff) {
            throw new Exception('Staff not found');
        }

        return $this->staffRepository->delete(
            $staffId
        );
    }


    // ========================================
    // Update Staff Status
    // ========================================

    public function updateStatus(
        int $staffId,
        string $status
    ): bool {

        if ($staffId <= 0) {
            throw new Exception('Invalid staff ID');
        }

        $allowedStatuses = [
            'Active',
            'Inactive'
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new Exception('Invalid staff status');
        }

        $staff = $this->staffRepository->getById(
            $staffId
        );

        if (!$staff) {
            throw new Exception('Staff not found');
        }

        return $this->staffRepository->updateStatus(
            $staffId,
            $status
        );
    }
}