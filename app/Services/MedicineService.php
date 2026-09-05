<?php

require_once __DIR__ . '/../Repositories/MedicineRepository.php';

class MedicineService
{
    private MedicineRepository $medicineRepository;

    public function __construct()
    {
        $this->medicineRepository = new MedicineRepository();
    }


    // ----------------------------------------
    // CREATE MEDICINE
    // ----------------------------------------

    public function createMedicine(
        int $tenantId,
        string $name,
        ?string $description,
        int $stockQuantity
    ): int {

        if (empty(trim($name))) {
            throw new Exception('Medicine name is required');
        }

        if ($stockQuantity < 0) {
            throw new Exception('Stock quantity cannot be negative');
        }

        return $this->medicineRepository->create(
            $tenantId,
            $name,
            $description,
            $stockQuantity
        );
    }


    // ----------------------------------------
    // GET ALL MEDICINES
    // ----------------------------------------

    public function getAllMedicines(int $tenantId): array
    {
        return $this->medicineRepository->getAll($tenantId);
    }


    // ----------------------------------------
    // GET SINGLE MEDICINE
    // ----------------------------------------

    public function getMedicineById(
        int $medicineId,
        int $tenantId
    ): array {

        $medicine = $this->medicineRepository->getById(
            $medicineId,
            $tenantId
        );

        if ($medicine === false) {
            throw new Exception('Medicine not found');
        }

        return $medicine;
    }


    // ----------------------------------------
    // UPDATE MEDICINE
    // ----------------------------------------

    public function updateMedicine(
        int $medicineId,
        int $tenantId,
        string $name,
        ?string $description,
        int $stockQuantity
    ): bool {

        if (empty(trim($name))) {
            throw new Exception('Medicine name is required');
        }

        if ($stockQuantity < 0) {
            throw new Exception('Stock quantity cannot be negative');
        }

        $medicine = $this->medicineRepository->getById(
            $medicineId,
            $tenantId
        );

        if ($medicine === false) {
            throw new Exception('Medicine not found');
        }

        return $this->medicineRepository->update(
            $medicineId,
            $tenantId,
            $name,
            $description,
            $stockQuantity
        );
    }


    // ----------------------------------------
    // DELETE MEDICINE
    // ----------------------------------------

    public function deleteMedicine(
        int $medicineId,
        int $tenantId
    ): bool {

        $medicine = $this->medicineRepository->getById(
            $medicineId,
            $tenantId
        );

        if ($medicine === false) {
            throw new Exception('Medicine not found');
        }

        return $this->medicineRepository->delete(
            $medicineId,
            $tenantId
        );
    }
}