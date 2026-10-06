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

    // GET /patients?page=N  (16 patients per page/batch, fixed by the service)
    public function index(int $tenantId, int $page = 1): void
    {
        try {
            $result = $this->patientService->getPatientsPage($page, $tenantId);

            Response::success(
                $result['patients'],
                'Patients fetched successfully',
                200,
                ['pagination' => $result['pagination']]
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