<?php

require_once __DIR__ . '/../Repositories/AppointmentRepository.php';

class AppointmentService
{
    private AppointmentRepository $appointmentRepository;

    private const VALID_STATUSES = [
        'Scheduled',
        'Confirmed',
        'Completed',
        'Cancelled',
        'No-Show'
    ];

    public function __construct(
        AppointmentRepository $appointmentRepository
    ) {
        $this->appointmentRepository = $appointmentRepository;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE APPOINTMENT
    |--------------------------------------------------------------------------
    */
    public function createAppointment(
        array $data,
        int $tenantId
    ): array {

        // Validate required fields
        $this->validateCore($data);

        // Check provider time-slot conflict
        $hasConflict = $this->appointmentRepository->hasConflict(
            (int) $data['provider_id'],
            $data['appointment_date'],
            $data['appointment_time']
        );

        if ($hasConflict) {
            throw new Exception(
                'This time slot is already booked for the selected provider'
            );
        }

        // Create appointment
        $id = $this->appointmentRepository->create([
            'patient_id'       => $data['patient_id'],
            'provider_id'      => $data['provider_id'],
            'appointment_date' => $data['appointment_date'],
            'appointment_time' => $data['appointment_time'],
            'reason'           => $data['reason'] ?? null
        ]);

        return $this->getAppointment(
            $id,
            $tenantId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE APPOINTMENT
    |--------------------------------------------------------------------------
    */
    public function updateAppointment(
        int $id,
        int $tenantId,
        array $data
    ): array {

        // Find existing appointment
        $existing = $this->appointmentRepository->findById($id);

        if (!$existing) {
            throw new Exception('Appointment not found');
        }

        // Cancelled appointment cannot be edited
        if ($existing['status'] === 'Cancelled') {
            throw new Exception(
                'Cannot update a cancelled appointment'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate fields only when they are supplied
        |--------------------------------------------------------------------------
        */

        if (isset($data['patient_id'])) {

            if (empty($data['patient_id'])) {
                throw new Exception(
                    'Patient is required'
                );
            }
        }

        if (isset($data['provider_id'])) {

            if (empty($data['provider_id'])) {
                throw new Exception(
                    'Provider is required'
                );
            }
        }

        if (isset($data['appointment_date'])) {

            if (
                empty($data['appointment_date']) ||
                !$this->isValidDate(
                    $data['appointment_date']
                )
            ) {
                throw new Exception(
                    'A valid appointment_date (YYYY-MM-DD) is required'
                );
            }
        }

        if (isset($data['appointment_time'])) {

            if (
                empty($data['appointment_time']) ||
                !$this->isValidTime(
                    $data['appointment_time']
                )
            ) {
                throw new Exception(
                    'A valid appointment_time (HH:MM) is required'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validate status
        |--------------------------------------------------------------------------
        */

        if (isset($data['status'])) {

            if (!in_array(
                $data['status'],
                self::VALID_STATUSES,
                true
            )) {
                throw new Exception(
                    'Invalid status. Allowed: ' .
                    implode(', ', self::VALID_STATUSES)
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Check provider time-slot conflict
        |--------------------------------------------------------------------------
        */

        if (
            isset($data['appointment_date']) ||
            isset($data['appointment_time']) ||
            isset($data['provider_id'])
        ) {

            $providerId = $data['provider_id']
                ?? $existing['provider_id'];

            $date = $data['appointment_date']
                ?? $existing['appointment_date'];

            $time = $data['appointment_time']
                ?? $existing['appointment_time'];

            $hasConflict =
                $this->appointmentRepository->hasConflict(
                    (int) $providerId,
                    $date,
                    $time,
                    $id
                );

            if ($hasConflict) {
                throw new Exception(
                    'This time slot is already booked for the selected provider'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Allowed fields
        |--------------------------------------------------------------------------
        */

        $allowed = [
            'patient_id',
            'provider_id',
            'appointment_date',
            'appointment_time',
            'reason',
            'status'
        ];

        $updates = array_intersect_key(
            $data,
            array_flip($allowed)
        );

        /*
        |--------------------------------------------------------------------------
        | Update database
        |--------------------------------------------------------------------------
        */

        if (!empty($updates)) {

            $this->appointmentRepository->update(
                $id,
                $updates
            );
        }

        return $this->getAppointment(
            $id,
            $tenantId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE STATUS
    |--------------------------------------------------------------------------
    */
    public function updateStatus(
        int $id,
        int $tenantId,
        string $status
    ): array {

        // Validate status
        if (!in_array(
            $status,
            self::VALID_STATUSES,
            true
        )) {
            throw new Exception(
                'Invalid status. Allowed: ' .
                implode(', ', self::VALID_STATUSES)
            );
        }

        // Find appointment
        $existing = $this->appointmentRepository->findById($id);

        if (!$existing) {
            throw new Exception(
                'Appointment not found'
            );
        }

        // Cannot change status after cancellation
        if ($existing['status'] === 'Cancelled') {
            throw new Exception(
                'Cannot change the status of a cancelled appointment'
            );
        }

        // Update status
        $this->appointmentRepository->updateStatus(
            $id,
            $status
        );

        return $this->getAppointment(
            $id,
            $tenantId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CANCEL APPOINTMENT
    |--------------------------------------------------------------------------
    */
    public function cancelAppointment(
        int $id,
        int $tenantId
    ): array {

        return $this->updateStatus(
            $id,
            $tenantId,
            'Cancelled'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GET SINGLE APPOINTMENT
    |--------------------------------------------------------------------------
    */
    public function getAppointment(
        int $id,
        int $tenantId
    ): array {

        $appointment =
            $this->appointmentRepository->findById($id);

        if (!$appointment) {
            throw new Exception(
                'Appointment not found'
            );
        }

        return $appointment;
    }


    /*
    |--------------------------------------------------------------------------
    | GET ALL APPOINTMENTS
    |--------------------------------------------------------------------------
    */
    public function getAllAppointments(
        int $tenantId
    ): array {

        return $this->appointmentRepository->findAll();
    }


    /*
    |--------------------------------------------------------------------------
    | CORE VALIDATION
    |--------------------------------------------------------------------------
    */
    private function validateCore(array $data): void
    {
        // Patient
        if (empty($data['patient_id'])) {
            throw new Exception(
                'Patient is required'
            );
        }

        // Provider
        if (empty($data['provider_id'])) {
            throw new Exception(
                'Provider is required'
            );
        }

        // Appointment date
        if (
            empty($data['appointment_date']) ||
            !$this->isValidDate(
                $data['appointment_date']
            )
        ) {
            throw new Exception(
                'A valid appointment_date (YYYY-MM-DD) is required'
            );
        }

        // Appointment time
        if (
            empty($data['appointment_time']) ||
            !$this->isValidTime(
                $data['appointment_time']
            )
        ) {
            throw new Exception(
                'A valid appointment_time (HH:MM) is required'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DATE VALIDATION
    |--------------------------------------------------------------------------
    */
    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat(
            'Y-m-d',
            $date
        );

        return $d !== false &&
            $d->format('Y-m-d') === $date;
    }


    /*
    |--------------------------------------------------------------------------
    | TIME VALIDATION
    |--------------------------------------------------------------------------
    */
    private function isValidTime(string $time): bool
    {
        return preg_match(
            '/^\d{2}:\d{2}(:\d{2})?$/',
            $time
        ) === 1;
    }
}