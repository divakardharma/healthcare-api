<?php

require_once __DIR__ . '/../Repositories/AppointmentRepository.php';

class CalendarService
{
    private AppointmentRepository $appointmentRepository;

    public function __construct(
        AppointmentRepository $appointmentRepository
    ) {
        $this->appointmentRepository = $appointmentRepository;
    }


    /*
    |--------------------------------------------------------------------------
    | DAY VIEW
    |--------------------------------------------------------------------------
    */
    public function getDayView(
        int $tenantId,
        string $date,
        ?int $providerId = null
    ): array {

        // Validate date
        if (!$this->isValidDate($date)) {
            throw new Exception(
                'A valid date (YYYY-MM-DD) is required'
            );
        }

        // Get appointments for the selected date
        $appointments =
            $this->appointmentRepository->getByDate($date);

        // Filter by provider if providerId is supplied
        return $this->filterByProvider(
            $appointments,
            $providerId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DATE RANGE VIEW
    |--------------------------------------------------------------------------
    */
    public function getRangeView(
        int $tenantId,
        string $startDate,
        string $endDate,
        ?int $providerId = null
    ): array {

        // Required fields
        if (
            empty($startDate) ||
            empty($endDate)
        ) {
            throw new Exception(
                'start_date and end_date are required'
            );
        }

        // Validate dates
        if (
            !$this->isValidDate($startDate) ||
            !$this->isValidDate($endDate)
        ) {
            throw new Exception(
                'start_date and end_date must be in YYYY-MM-DD format'
            );
        }

        // Start date cannot be after end date
        if ($startDate > $endDate) {
            throw new Exception(
                'start_date cannot be after end_date'
            );
        }

        // Get appointments within range
        $appointments =
            $this->appointmentRepository->getByDateRange(
                $startDate,
                $endDate
            );

        // Filter by provider if supplied
        return $this->filterByProvider(
            $appointments,
            $providerId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPCOMING APPOINTMENTS
    |--------------------------------------------------------------------------
    */
    public function getUpcoming(
        int $tenantId,
        ?int $providerId = null
    ): array {

        $appointments =
            $this->appointmentRepository
                ->getUpcomingAppointments();

        return $this->filterByProvider(
            $appointments,
            $providerId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | APPOINTMENT TOOLTIP
    |--------------------------------------------------------------------------
    */
    public function getTooltip(
        int $tenantId,
        int $appointmentId
    ): array {

        $appointment =
            $this->appointmentRepository
                ->findById($appointmentId);

        if (!$appointment) {
            throw new Exception(
                'Appointment not found'
            );
        }

        return [
            'id'       => (int) $appointment['id'],
            'patient'  => $appointment['patient_name'] ?? null,
            'provider' => $appointment['provider_name'] ?? null,
            'date'     => $appointment['appointment_date'],
            'time'     => $appointment['appointment_time'],
            'status'   => $appointment['status'],
            'reason'   => $appointment['reason']
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | FILTER BY PROVIDER
    |--------------------------------------------------------------------------
    */
    private function filterByProvider(
        array $appointments,
        ?int $providerId
    ): array {

        // No provider filter
        if ($providerId === null) {
            return $appointments;
        }

        // Filter appointments for selected provider
        return array_values(
            array_filter(
                $appointments,
                fn ($appointment) =>
                    (int) $appointment['provider_id'] === $providerId
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DATE VALIDATION
    |--------------------------------------------------------------------------
    */
    private function isValidDate(
        string $date
    ): bool {

        $d = DateTime::createFromFormat(
            'Y-m-d',
            $date
        );

        return $d !== false &&
            $d->format('Y-m-d') === $date;
    }
}