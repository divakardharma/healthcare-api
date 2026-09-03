<?php

require_once __DIR__ . '/../Repositories/AppointmentRepository.php';

class AppointmentService
{
    private AppointmentRepository $appointmentRepository;

    private const VALID_STATUSES = ['Scheduled', 'Confirmed', 'Completed', 'Cancelled', 'No-Show'];

    public function __construct(AppointmentRepository $appointmentRepository)
    {
        $this->appointmentRepository = $appointmentRepository;
    }

    public function createAppointment(array $data, int $tenantId): array
    {
        $this->validateCore($data);

        $hasConflict = $this->appointmentRepository->hasConflict(
            (int) $data['provider_id'],
            $data['appointment_date'],
            $data['appointment_time']
        );

        if ($hasConflict) {
            throw new Exception('This time slot is already booked for the selected provider');
        }

        $id = $this->appointmentRepository->create([
            'tenant_id'        => $tenantId,
            'patient_id'       => $data['patient_id'],
            'provider_id'      => $data['provider_id'],
            'appointment_date' => $data['appointment_date'],
            'appointment_time' => $data['appointment_time'],
            'reason'           => $data['reason'] ?? null
        ]);

        return $this->getAppointment($id, $tenantId);
    }

    public function updateAppointment(int $id, int $tenantId, array $data): array
    {
        $existing = $this->appointmentRepository->findByIdForTenant($id, $tenantId);

        if (!$existing) {
            throw new Exception('Appointment not found');
        }

        if ($existing['status'] === 'Cancelled') {
            throw new Exception('Cannot update a cancelled appointment');
        }

        if (isset($data['appointment_date']) || isset($data['appointment_time']) || isset($data['provider_id'])) {
            $providerId = $data['provider_id'] ?? $existing['provider_id'];
            $date = $data['appointment_date'] ?? $existing['appointment_date'];
            $time = $data['appointment_time'] ?? $existing['appointment_time'];

            $hasConflict = $this->appointmentRepository->hasConflict(
                (int) $providerId,
                $date,
                $time,
                $id
            );

            if ($hasConflict) {
                throw new Exception('This time slot is already booked for the selected provider');
            }
        }

        $allowed = ['patient_id', 'provider_id', 'appointment_date', 'appointment_time', 'reason', 'status'];
        $updates = array_intersect_key($data, array_flip($allowed));

        if (!empty($updates)) {
            $this->appointmentRepository->update($id, $tenantId, $updates);
        }

        return $this->getAppointment($id, $tenantId);
    }

    public function updateStatus(int $id, int $tenantId, string $status): array
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new Exception('Invalid status. Allowed: ' . implode(', ', self::VALID_STATUSES));
        }

        $existing = $this->appointmentRepository->findByIdForTenant($id, $tenantId);

        if (!$existing) {
            throw new Exception('Appointment not found');
        }

        $this->appointmentRepository->updateStatus($id, $tenantId, $status);

        return $this->getAppointment($id, $tenantId);
    }

    public function cancelAppointment(int $id, int $tenantId): array
    {
        return $this->updateStatus($id, $tenantId, 'Cancelled');
    }

    public function getAppointment(int $id, int $tenantId): array
    {
        $appointment = $this->appointmentRepository->findByIdForTenant($id, $tenantId);

        if (!$appointment) {
            throw new Exception('Appointment not found');
        }

        return $appointment;
    }

    public function getAllAppointments(int $tenantId): array
    {
        return $this->appointmentRepository->findAllByTenant($tenantId);
    }

    private function validateCore(array $data): void
    {
        if (empty($data['patient_id'])) {
            throw new Exception('Patient is required');
        }

        if (empty($data['provider_id'])) {
            throw new Exception('Provider is required');
        }

        if (empty($data['appointment_date']) || !$this->isValidDate($data['appointment_date'])) {
            throw new Exception('A valid appointment_date (YYYY-MM-DD) is required');
        }

        if (empty($data['appointment_time']) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $data['appointment_time'])) {
            throw new Exception('A valid appointment_time (HH:MM) is required');
        }
    }

    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);

        return $d && $d->format('Y-m-d') === $date;
    }
}