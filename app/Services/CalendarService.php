<?php

require_once __DIR__ . '/../Repositories/AppointmentRepository.php';

class CalendarService
{
    private AppointmentRepository $appointmentRepository;

    public function __construct(AppointmentRepository $appointmentRepository)
    {
        $this->appointmentRepository = $appointmentRepository;
    }

    public function getDayView(int $tenantId, string $date, ?int $providerId = null): array
    {
        if (!$this->isValidDate($date)) {
            throw new Exception('A valid date (YYYY-MM-DD) is required');
        }

        $appointments = $this->appointmentRepository->getByDate($tenantId, $date);

        return $this->filterByProvider($appointments, $providerId);
    }

    public function getRangeView(int $tenantId, string $startDate, string $endDate, ?int $providerId = null): array
    {
        if (empty($startDate) || empty($endDate)) {
            throw new Exception('start_date and end_date are required');
        }

        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            throw new Exception('start_date and end_date must be in YYYY-MM-DD format');
        }

        if ($startDate > $endDate) {
            throw new Exception('start_date cannot be after end_date');
        }

        $appointments = $this->appointmentRepository->getByDateRange($tenantId, $startDate, $endDate);

        return $this->filterByProvider($appointments, $providerId);
    }

    public function getUpcoming(int $tenantId, ?int $providerId = null): array
    {
        $appointments = $this->appointmentRepository->getUpcomingAppointments($tenantId);

        return $this->filterByProvider($appointments, $providerId);
    }

    public function getTooltip(int $tenantId, int $appointmentId): array
    {
        $appointment = $this->appointmentRepository->findByIdForTenant($appointmentId, $tenantId);

        if (!$appointment) {
            throw new Exception('Appointment not found');
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

    private function filterByProvider(array $appointments, ?int $providerId): array
    {
        if ($providerId === null) {
            return $appointments;
        }

        return array_values(array_filter(
            $appointments,
            fn ($appointment) => (int) $appointment['provider_id'] === $providerId
        ));
    }

    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);

        return $d && $d->format('Y-m-d') === $date;
    }
}