<?php

require_once __DIR__ . '/../Services/AppointmentService.php';
require_once __DIR__ . '/../Helpers/Response.php';

class AppointmentController
{
    private AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }


    // POST /appointments
    // Provider, Nurse, Admin
    public function create(array $data, int $tenantId): void
    {
        try {

            $appointment =
                $this->appointmentService->createAppointment(
                    $data,
                    $tenantId
                );

            Response::success(
                $appointment,
                'Appointment created successfully',
                201
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                422
            );
        }
    }


    // GET /appointments/{id}
    public function show(int $id, int $tenantId): void
    {
        try {

            $appointment =
                $this->appointmentService->getAppointment(
                    $id,
                    $tenantId
                );

            Response::success(
                $appointment,
                'Appointment fetched successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                404
            );
        }
    }


    // GET /appointments
    public function index(int $tenantId): void
    {
        try {

            $appointments =
                $this->appointmentService->getAllAppointments(
                    $tenantId
                );

            Response::success(
                $appointments,
                'Appointments fetched successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                422
            );
        }
    }


    // PUT /appointments/{id}
    // Provider, Nurse, Admin
    public function update(
        int $id,
        array $data,
        int $tenantId
    ): void {

        try {

            $appointment =
                $this->appointmentService->updateAppointment(
                    $id,
                    $tenantId,
                    $data
                );

            Response::success(
                $appointment,
                'Appointment updated successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                422
            );
        }
    }


    // PATCH /appointments/{id}/status
    // { "status": "Completed" }
    public function updateStatus(
        int $id,
        array $data,
        int $tenantId
    ): void {

        // Status required
        if (
            !isset($data['status']) ||
            trim((string) $data['status']) === ''
        ) {

            Response::error(
                'Status is required',
                422
            );
        }

        try {

            $appointment =
                $this->appointmentService->updateStatus(
                    $id,
                    $tenantId,
                    $data['status']
                );

            Response::success(
                $appointment,
                'Appointment status updated'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                422
            );
        }
    }


    // PUT /appointments/{id}/cancel
    public function cancel(
        int $id,
        int $tenantId
    ): void {

        try {

            $appointment =
                $this->appointmentService->cancelAppointment(
                    $id,
                    $tenantId
                );

            Response::success(
                $appointment,
                'Appointment cancelled successfully'
            );

        } catch (Exception $e) {

            Response::error(
                $e->getMessage(),
                422
            );
        }
    }
}