<?php
require_once __DIR__ . '/../Repositories/BillingRepository.php';
class BillingService
{
    private BillingRepository $billingRepository;
    public function __construct(PDO $pdo)
    {
        $this->billingRepository = new BillingRepository($pdo);
    }
    
    // Create Invoice
    public function createInvoice(
        int $patientId,
        ?int $appointmentId,
        float $amount
    ): int {
        if ($patientId <= 0) {
            throw new Exception('Valid patient ID is required');
        }
        if ($amount <= 0) {
            throw new Exception('Valid amount is required');
        }
        $invoiceNumber = 'INV-' . date('YmdHis') . '-' . rand(100, 999);
        return $this->billingRepository->createInvoice(
            $patientId,
            $appointmentId,
            $invoiceNumber,
            $amount
        );
    }
    // ========================================
    // Get All Invoices
    // ========================================
    public function getAllInvoices(): array
    {
        return $this->billingRepository->getAll();
    }
    // ========================================
    // Get Invoice By ID
    // ========================================
    public function getInvoiceById(int $billingId): array
    {
        if ($billingId <= 0) {
            throw new Exception('Invalid invoice ID');
        }
        $invoice = $this->billingRepository->getById($billingId);
        if (!$invoice) {
            throw new Exception('Invoice not found');
        }
        return $invoice;
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
        if ($billingId <= 0) {
            throw new Exception('Invalid invoice ID');
        }
        $invoice = $this->billingRepository->getById($billingId);
        if (!$invoice) {
            throw new Exception('Invoice not found');
        }
        if ($patientId <= 0) {
            throw new Exception('Valid patient ID is required');
        }
        if (empty($invoiceNumber)) {
            throw new Exception('Invoice number is required');
        }
        if ($amount <= 0) {
            throw new Exception('Valid amount is required');
        }
        $allowedStatuses = ['Pending', 'Paid'];
        if (!in_array($paymentStatus, $allowedStatuses, true)) {
            throw new Exception('Invalid payment status');
        }
        return $this->billingRepository->updateInvoice(
            $billingId,
            $patientId,
            $appointmentId,
            $invoiceNumber,
            $amount,
            $paymentStatus
        );
    }
    // ========================================
    // Delete Invoice
    // ========================================
    public function deleteInvoice(int $billingId): bool
    {
        if ($billingId <= 0) {
            throw new Exception('Invalid invoice ID');
        }
        $invoice = $this->billingRepository->getById($billingId);
        if (!$invoice) {
            throw new Exception('Invoice not found');
        }
        return $this->billingRepository->deleteInvoice($billingId);
    }
    // ========================================
    // Update Payment Status
    // ========================================
    public function updatePaymentStatus(
        int $billingId,
        string $paymentStatus
    ): void {
        if ($billingId <= 0) {
            throw new Exception('Invalid invoice ID');
        }
        $allowedStatuses = ['Pending', 'Paid'];
        if (!in_array($paymentStatus, $allowedStatuses, true)) {
            throw new Exception('Invalid payment status');
        }
        $invoice = $this->billingRepository->getById($billingId);
        if (!$invoice) {
            throw new Exception('Invoice not found');
        }
        $this->billingRepository->updatePaymentStatus(
            $billingId,
            $paymentStatus
        );
    }
    // ========================================
    // Get Payment Summary
    // ========================================
    public function getPaymentSummary(): array
    {
        $summary = $this->billingRepository->getPaymentSummary();
        return [
            'total' => (int)($summary['total'] ?? 0),
            'pending' => (int)($summary['pending'] ?? 0),
            'paid' => (int)($summary['paid'] ?? 0)
        ];
    }
}