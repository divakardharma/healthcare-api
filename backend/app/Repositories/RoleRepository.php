<?php

class RoleRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByName(string $name): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE name = :name");
        $stmt->execute([':name' => $name]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM roles ORDER BY name ASC");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignToUser(int $userId, int $roleId): bool
    {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)"
        );

        return $stmt->execute([
            ':user_id' => $userId,
            ':role_id' => $roleId
        ]);
    }

    public function removeFromUser(int $userId, int $roleId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM user_roles WHERE user_id = :user_id AND role_id = :role_id"
        );

        return $stmt->execute([
            ':user_id' => $userId,
            ':role_id' => $roleId
        ]);
    }

    public function getRolesForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.id, r.name
             FROM roles r
             INNER JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = :user_id"
        );

        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}