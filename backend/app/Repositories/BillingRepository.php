<?php

class BillingRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create an invoice. Keep invoice_number generation in the service layer
     * if your existing service already creates it.
     */
    public function createInvoice(
        int $patientId,
        ?int $appointmentId,
        string $invoiceNumber,
        float $amount
    ): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO billing
                (patient_id, appointment_id, invoice_number, amount, payment_status)
             VALUES
                (:patient_id, :appointment_id, :invoice_number, :amount, 'Pending')"
        );

        $stmt->execute([
            ':patient_id' => $patientId,
            ':appointment_id' => $appointmentId,
            ':invoice_number' => $invoiceNumber,
            ':amount' => $amount,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Fetch invoices newest first. JOINs provide display names in one query.
     * Confirm patient name column/table names match your actual schema.
     */
    public function getAll(): array
    {
        $sql = "
            SELECT
                b.id,
                b.patient_id,
                b.appointment_id,
                b.invoice_number,
                b.amount,
                b.payment_status,
                p.patient_name
            FROM billing AS b
            LEFT JOIN patients AS p ON p.id = b.patient_id
            ORDER BY b.id DESC
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $billingId): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                b.id,
                b.patient_id,
                b.appointment_id,
                b.invoice_number,
                b.amount,
                b.payment_status,
                p.patient_name
             FROM billing AS b
             LEFT JOIN patients AS p ON p.id = b.patient_id
             WHERE b.id = :id
             LIMIT 1"
        );

        $stmt->execute([':id' => $billingId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    public function updateInvoice(
        int $billingId,
        int $patientId,
        ?int $appointmentId,
        string $invoiceNumber,
        float $amount,
        string $paymentStatus
    ): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE billing
             SET patient_id = :patient_id,
                 appointment_id = :appointment_id,
                 invoice_number = :invoice_number,
                 amount = :amount,
                 payment_status = :payment_status
             WHERE id = :id"
        );

        return $stmt->execute([
            ':patient_id' => $patientId,
            ':appointment_id' => $appointmentId,
            ':invoice_number' => $invoiceNumber,
            ':amount' => $amount,
            ':payment_status' => $paymentStatus,
            ':id' => $billingId,
        ]);
    }

    public function deleteInvoice(int $billingId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM billing WHERE id = :id");
        $stmt->execute([':id' => $billingId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Invoice not found');
        }

        return true;
    }

    public function updatePaymentStatus(int $billingId, string $paymentStatus): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE billing
             SET payment_status = :payment_status
             WHERE id = :id"
        );

        return $stmt->execute([
            ':payment_status' => $paymentStatus,
            ':id' => $billingId,
        ]);
    }

    /**
     * One aggregate scan returns all summary values.
     * COUNT(*) and conditional SUMs avoid separate queries per metric.
     */
    /**
     * Lightweight list for the invoice form dropdown: id + (encrypted) name only.
     * Replaces loading the paginated /patients endpoint.
     */
    public function getPatientOptions(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, patient_name
             FROM patients
             WHERE deleted_at IS NULL
             ORDER BY id DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Appointments of ONE patient (uses the patient_id foreign-key index).
     */
    public function getAppointmentOptions(int $patientId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                a.id,
                a.patient_id,
                a.appointment_date,
                a.appointment_time,
                a.status,
                u.name AS provider_name
             FROM appointments a
             LEFT JOIN users u ON u.id = a.provider_id
             WHERE a.patient_id = :patient_id
             ORDER BY
                a.appointment_date DESC,
                a.appointment_time DESC,
                a.id DESC"
        );

        $stmt->execute([':patient_id' => $patientId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaymentSummary(): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total_invoices,
                COALESCE(SUM(amount), 0) AS total_amount,
                COALESCE(SUM(CASE WHEN payment_status = 'Paid' THEN amount ELSE 0 END), 0) AS paid_amount,
                COALESCE(SUM(CASE WHEN payment_status = 'Pending' THEN amount ELSE 0 END), 0) AS pending_amount
            FROM billing
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_invoices' => 0,
            'total_amount' => 0,
            'paid_amount' => 0,
            'pending_amount' => 0,
        ];
    }
}