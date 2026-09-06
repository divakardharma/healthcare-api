<?php
require_once __DIR__ . '/../Config/database.php';
class BillingRepository
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    // ========================================
    // Create Invoice
    // ========================================
    public function createInvoice(
        int $patientId,
        ?int $appointmentId,
        string $invoiceNumber,
        float $amount
    ): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO billing
            (
                patient_id,
                appointment_id,
                invoice_number,
                amount,
                payment_status
            )
            VALUES (?, ?, ?, ?, 'Pending')"
        );
        $stmt->execute([
            $patientId,
            $appointmentId,
            $invoiceNumber,
            $amount
        ]);
        return (int)$this->pdo->lastInsertId();
    }
    // ========================================
    // Get All Invoices
    // ========================================
    public function getAll(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM billing ORDER BY id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
    // ========================================
    // Get Invoice By ID
    // ========================================
    public function getById(int $billingId): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM billing WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$billingId]);
        return $stmt->fetch() ?: false;
    }
    // ========================================
    // Update Invoice
    // ========================================
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
            SET patient_id = ?,
                appointment_id = ?,
                invoice_number = ?,
                amount = ?,
                payment_status = ?
            WHERE id = ?"
        );
        return $stmt->execute([
            $patientId,
            $appointmentId,
            $invoiceNumber,
            $amount,
            $paymentStatus,
            $billingId
        ]);
    }
    // ========================================
    // Delete Invoice
    // ========================================
    public function deleteInvoice(int $billingId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM billing WHERE id = ?"
        );
        $stmt->execute([$billingId]);
        if ($stmt->rowCount() === 0) {
            throw new Exception('Invoice not found');
        }
        return true;
    }
    // ========================================
    // Update Payment Status
    // ========================================
    public function updatePaymentStatus(
        int $billingId,
        string $paymentStatus
    ): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE billing
            SET payment_status = ?
            WHERE id = ?"
        );
        return $stmt->execute([
            $paymentStatus,
            $billingId
        ]);
    }
    // ========================================
    // Payment Summary
    // ========================================
    public function getPaymentSummary(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(payment_status = 'Pending') AS pending,
                SUM(payment_status = 'Paid') AS paid
            FROM billing"
        );
        $stmt->execute();
        return $stmt->fetch();
    }
}