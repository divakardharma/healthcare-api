<?php

class TenantService
{
    private PDO $pdo;
    private TenantProvisioningService $provisioningService;

    public function __construct(
        PDO $pdo,
        TenantProvisioningService $provisioningService
    ) {
        $this->pdo = $pdo;
        $this->provisioningService = $provisioningService;
    }

    public function registerTenant(
        string $name,
        string $email,
        string $subdomain,
        string $password
    ): array {



    
        // Check email
        $stmt = $this->pdo->prepare(
            "SELECT id FROM tenants WHERE email = ? LIMIT 1"
        );

        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            throw new Exception("Email already registered.");
        }

        // Check subdomain
        $stmt = $this->pdo->prepare(
            "SELECT id FROM tenants WHERE subdomain = ? LIMIT 1"
        );

        $stmt->execute([$subdomain]);

        if ($stmt->fetch()) {
            throw new Exception("Subdomain already exists.");
        }

        // Create tenant in Master DB
        $stmt = $this->pdo->prepare("
            INSERT INTO tenants ( name, email, subdomain, status, trial_end, subscription_status )
            VALUES(?,?, ?, 'provisioning', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'trial' )");

        $stmt->execute([ $name, $email, $subdomain]);

        $tenantId = (int) $this->pdo->lastInsertId();

        // Provision tenant database + create admin
        $databaseName = $this->provisioningService->provisionTenant( $tenantId, $name, $email, $password );

        return [
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => $email,
            'subdomain' => $subdomain,
            'database' => $databaseName,
            'status' => 'active'
        ];
    }
}