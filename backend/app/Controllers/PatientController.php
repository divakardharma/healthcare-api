<?php

require_once __DIR__ . '/../Services/PatientService.php';
require_once __DIR__ . '/../Helpers/Response.php';

class PatientController
{
    private PatientService $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    // POST /patients  (Provider, Nurse, Admin)
    public function create(array $data, int $tenantId): void
    {
        try {
            $patient = $this->patientService->createPatient($data, $tenantId);
            Response::success($patient, 'Patient created successfully', 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 422);
        }
    }

    // GET /patients/{id}
    public function show(int $id, int $tenantId): void
    {
        try {
            $patient = $this->patientService->getPatientWithAppointments($id, $tenantId);
            Response::success($patient, 'Patient fetched successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    // GET /patients
public function index(int $tenantId): void
{
    try {
        $patients = $this->patientService->getAllPatients($tenantId);

        Response::success(
            $patients,
            'Patients fetched successfully'
        );
    } catch (Exception $e) {
        Response::error(
            $e->getMessage(),
            422
        );
    }
}
    // PUT /patients/{id}  (Provider, Nurse, Admin)
    public function update(int $id, array $data, int $tenantId): void
    {
        try {
            $patient = $this->patientService->updatePatient($id, $tenantId, $data);
            Response::success($patient, 'Patient updated successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 422);
        }
    }

    // DELETE /patients/{id}  (Provider, Nurse, Admin) — soft delete
    public function delete(int $id, int $tenantId): void
    {
        try {
            $this->patientService->deletePatient($id, $tenantId);
            Response::success(null, 'Patient deleted successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }
}