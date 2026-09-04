<?php

class TenantResolver
{
    private PDO $masterPdo;

    public function __construct(PDO $masterPdo)
    {
        $this->masterPdo = $masterPdo;
    }

    public function resolve(string $subdomain): array
    {
        $stmt = $this->masterPdo->prepare("
            SELECT
                id,
                name,
                email,
                subdomain,
                status,
                trial_end,
                subscription_status,
                db_name,
                db_host
            FROM tenants
            WHERE subdomain = ?
            LIMIT 1
        ");

        $stmt->execute([$subdomain]);

        $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tenant) {
            throw new Exception('Tenant not found');
        }

        if ($tenant['status'] !== 'active') {
            throw new Exception('Tenant is not active');
        }

        if (empty($tenant['db_name'])) {
            throw new Exception('Tenant database is not configured');
        }

        return $tenant;
    }

    public function resolveById(int $tenantId): array
{
    $stmt = $this->masterPdo->prepare("
        SELECT
            id,
            name,
            email,
            subdomain,
            status,
            trial_end,
            subscription_status,
            db_name,
            db_host
        FROM tenants
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$tenantId]);

    $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tenant) {
        throw new Exception('Tenant not found');
    }

    if ($tenant['status'] !== 'active') {
        throw new Exception('Tenant is not active');
    }

    if (empty($tenant['db_name'])) {
        throw new Exception('Tenant database is not configured');
    }

    return $tenant;
}

    public function connect(array $tenant): PDO
    {
        $host = $tenant['db_host'] ?: $_ENV['DB_HOST'];
        $database = $tenant['db_name'];

        $pdo = new PDO(
            "mysql:host={$host};dbname={$database};charset=utf8mb4",
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD']
        );

        $pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );

        return $pdo;
    }
}