<?php

require_once __DIR__ . '/../Services/BillingService.php';

class BillingController
{
    private BillingService $billingService;

    public function __construct(PDO $pdo)
    {
        $this->billingService = new BillingService($pdo);
    }


    // ========================================
    // Create Invoice
    // ========================================

    public function create(array $input): array
    {
        $patientId = $input['patient_id'] ?? 0;

        $appointmentId =
            $input['appointment_id'] ?? null;

        $amount = $input['amount'] ?? 0;

        // Create the invoice using the service
        $billingId =
            $this->billingService->createInvoice(
                (int)$patientId,
                $appointmentId
                    ? (int)$appointmentId
                    : null,
                (float)$amount
            );

        return [
            'message' => 'Invoice created successfully',
            'billing_id' => $billingId
        ];
    }


    // ========================================
    // Get All Invoices
    // ========================================

    public function getAll(): array
    {
        $invoices =
            $this->billingService->getAllInvoices();

        return [
            'message' => 'Invoices fetched successfully',
            'data' => $invoices
        ];
    }


    // ========================================
    // Get Invoice By ID
    // ========================================

    public function getById(
        int $billingId
    ): array {

        $invoice =
            $this->billingService->getInvoiceById(
                $billingId
            );

        return [
            'message' => 'Invoice fetched successfully',
            'data' => $invoice
        ];
    }


    // ========================================
    // Update Invoice
    // ========================================

    public function update(int $billingId,array $input): array {

        $patientId = $input['patient_id'] ?? 0;

        $appointmentId =
            $input['appointment_id'] ?? null;

        $invoiceNumber =
            $input['invoice_number'] ?? '';

        $amount =
            $input['amount'] ?? 0;

        $paymentStatus =
            $input['payment_status'] ?? 'Pending';

        $this->billingService->updateInvoice(
            $billingId,
            (int)$patientId,
            $appointmentId
                ? (int)$appointmentId
                : null,
            $invoiceNumber,
            (float)$amount,
            $paymentStatus
        );

        return [
            'message' => 'Invoice updated successfully'
        ];
    }


    // ========================================
    // Delete Invoice
    // ========================================

    public function delete(int $billingId): array {

        $this->billingService->deleteInvoice(
            $billingId
        );

        return [
            'message' => 'Invoice deleted successfully'
        ];
    }


    // ========================================
    // Update Payment Status
    // ========================================

    public function updatePaymentStatus(
        int $billingId,array $input): array {

        $paymentStatus =
            $input['payment_status'] ?? '';

        $this->billingService->updatePaymentStatus(
            $billingId,
            $paymentStatus
        );

        return [
            'message' =>'Payment status updated successfully'
        ];
    }


    // ========================================
    // Get Payment Summary
    // ========================================

    public function getPaymentSummary(): array
    {
        $summary =
            $this->billingService
                ->getPaymentSummary();

        return [
            'message' =>
                'Payment summary fetched successfully',
            'data' => $summary
        ];
    }
}