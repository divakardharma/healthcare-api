<?php

class TenantProvisioningService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function provisionTenant( int $tenantId, string $adminName, string $adminEmail, string $adminPassword ): string {

        $tenantDatabase = 'heal_tenant_' . $tenantId;

        /*
        |--------------------------------------------------------------------------
        | 1. Create Tenant Database
        |--------------------------------------------------------------------------
        */

        $this->pdo->exec( "CREATE DATABASE IF NOT EXISTS `$tenantDatabase`" );


        /*
        |--------------------------------------------------------------------------
        | 2. Read Tenant Schema
        |--------------------------------------------------------------------------
        */

        $schemaPath = __DIR__ . '/../../../database/tenant_schema.sql';

        if (!file_exists($schemaPath)) {
            throw new Exception("Tenant schema file not found.");
        }

        $schema = file_get_contents($schemaPath);

        if ($schema === false) {
            throw new Exception("Unable to read tenant schema.");
        }


        /*
        |--------------------------------------------------------------------------
        | 3. Connect to Tenant Database
        |--------------------------------------------------------------------------
        */

        $tenantPdo = new PDO(
             "mysql:host=" . $_ENV['DB_HOST'] .
            ";dbname=" . $tenantDatabase .
            ";charset=utf8mb4",
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD']
        );

        $tenantPdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tenantPdo->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );


        /*
        |--------------------------------------------------------------------------
        | 4. Execute Tenant Schema
        |--------------------------------------------------------------------------
        */

        $statements = array_filter( array_map( 'trim', explode(';', $schema) )  );

        foreach ($statements as $statement) {

            if ($statement !== '') {
                $tenantPdo->exec($statement);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 5. Create Default Admin User
        |--------------------------------------------------------------------------
        */

        $hashedPassword = password_hash( $adminPassword, PASSWORD_DEFAULT );

        $stmt = $tenantPdo->prepare("
            INSERT INTO users
            (
                name,
                email,
                password
            )
            VALUES
            (?, ?, ?)
        ");

        $stmt->execute([
            $adminName,
            $adminEmail,
            $hashedPassword
        ]);

        $adminUserId = (int) $tenantPdo->lastInsertId();


        /*
        |--------------------------------------------------------------------------
        | 6. Get Admin Role
        |--------------------------------------------------------------------------
        */

        $stmt = $tenantPdo->prepare("
            SELECT id
            FROM roles
            WHERE name = 'Admin'
            LIMIT 1
        ");

        $stmt->execute();

        $adminRole = $stmt->fetch();

        if (!$adminRole) {
            throw new Exception("Admin role not found.");
        }

        $adminRoleId = (int) $adminRole['id'];


        /*
        |--------------------------------------------------------------------------
        | 7. Assign Admin Role
        |--------------------------------------------------------------------------
        */

        $stmt = $tenantPdo->prepare("
            INSERT INTO user_roles
            (
                user_id,
                role_id
            )
            VALUES
            (?, ?)
        ");

        $stmt->execute([
            $adminUserId,
            $adminRoleId
        ]);


        /*
        |--------------------------------------------------------------------------
        | 8. Update Master DB
        |--------------------------------------------------------------------------
        */

        $stmt = $this->pdo->prepare("
            UPDATE tenants
            SET
                status = 'active',
                db_name = ?,
                db_host = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $tenantDatabase,
            $_ENV['DB_HOST'],
            $tenantId
        ]);

        return $tenantDatabase;
    }
}