<?php

require_once __DIR__ . '/../Services/MedicineService.php';

class MedicineController
{
    private MedicineService $medicineService;

    public function __construct()
    {
        $this->medicineService = new MedicineService();
    }


    // ----------------------------------------
    // CREATE MEDICINE
    // ----------------------------------------

    public function create(): array
    {
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );

        $tenantId = (int) ($input['tenant_id'] ?? 0);
        $name = $input['name'] ?? '';
        $description = $input['description'] ?? null;
        $stockQuantity = (int) ($input['stock_quantity'] ?? 0);

        if ($tenantId <= 0) {
            throw new Exception('Valid tenant ID is required');
        }

        $medicineId = $this->medicineService->createMedicine(
            $tenantId,
            $name,
            $description,
            $stockQuantity
        );

        return [
            'message' => 'Medicine created successfully',
            'data' => [
                'medicine_id' => $medicineId
            ]
        ];
    }


    // ----------------------------------------
    // GET ALL MEDICINES
    // ----------------------------------------

    public function getAll(): array
    {
        $tenantId = (int) ($_GET['tenant_id'] ?? 0);

        if ($tenantId <= 0) {
            throw new Exception('Valid tenant ID is required');
        }

        $medicines = $this->medicineService->getAllMedicines(
            $tenantId
        );

        return [
            'message' => 'Medicines retrieved successfully',
            'data' => $medicines
        ];
    }


    // ----------------------------------------
    // GET SINGLE MEDICINE
    // ----------------------------------------

    public function getById(int $medicineId): array
    {
        $tenantId = (int) ($_GET['tenant_id'] ?? 0);

        if ($tenantId <= 0) {
            throw new Exception('Valid tenant ID is required');
        }

        $medicine = $this->medicineService->getMedicineById(
            $medicineId,
            $tenantId
        );

        return [
            'message' => 'Medicine retrieved successfully',
            'data' => $medicine
        ];
    }


    // ----------------------------------------
    // UPDATE MEDICINE
    // ----------------------------------------

    public function update(int $medicineId): array
    {
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );

        $tenantId = (int) ($input['tenant_id'] ?? 0);
        $name = $input['name'] ?? '';
        $description = $input['description'] ?? null;
        $stockQuantity = (int) ($input['stock_quantity'] ?? 0);

        if ($tenantId <= 0) {
            throw new Exception('Valid tenant ID is required');
        }

        $this->medicineService->updateMedicine(
            $medicineId,
            $tenantId,
            $name,
            $description,
            $stockQuantity
        );

        return [
            'message' => 'Medicine updated successfully'
        ];
    }


    // ----------------------------------------
    // DELETE MEDICINE
    // ----------------------------------------

    public function delete(int $medicineId): array
    {
        $tenantId = (int) ($_GET['tenant_id'] ?? 0);

        if ($tenantId <= 0) {
            throw new Exception('Valid tenant ID is required');
        }

        $this->medicineService->deleteMedicine(
            $medicineId,
            $tenantId
        );

        return [
            'message' => 'Medicine deleted successfully'
        ];
    }
}