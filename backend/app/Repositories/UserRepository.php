<?php

class UserRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO users (tenant_id, name, email, password)
                VALUES (:tenant_id, :name, :email, :password)";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':tenant_id' => $data['tenant_id'],
            ':name'      => $data['name'],
            ':email'     => $data['email'],
            ':password'  => $data['password']
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['name', 'email'] as $column) {
            if (array_key_exists($column, $data)) {
                $fields[] = "{$column} = :{$column}";
                $params[":{$column}"] = $data[$column];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET password = :password WHERE id = :id"
        );

        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $id
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");

        return $stmt->execute([':id' => $id]);
    }

    public function findByEmail(string $email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findAllByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE tenant_id = :tenant_id ORDER BY id DESC"
        );
        $stmt->execute([':tenant_id' => $tenantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 public function findAllByTenantAndRole(int $tenantId, string $roleName): array
{
    $stmt = $this->db->prepare(
        "SELECT u.* FROM users u
         INNER JOIN user_roles ur ON ur.user_id = u.id
         INNER JOIN roles r ON r.id = ur.role_id
         WHERE u.tenant_id = :tenant_id AND r.name = :role_name
         ORDER BY u.id DESC"
    );

    $stmt->execute([
        ':tenant_id' => $tenantId,
        ':role_name' => $roleName
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function countByTenantId(int $tenantId): int
{
    $stmt = $this->db->prepare(
        "SELECT COUNT(*) FROM users WHERE tenant_id = :tenant_id"
    );

    $stmt->execute([
        ':tenant_id' => $tenantId
    ]);

    return (int)$stmt->fetchColumn();
}

}