<?php

class UserRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE USER
    |--------------------------------------------------------------------------
    */

    public function create(array $data): int
    {
        $sql = "INSERT INTO users
                (name, email, password)
                VALUES
                (:name, :email, :password)";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':name'     => $data['name'],
            ':email'    => $data['email'],
            ':password' => $data['password']
        ]);

        return (int) $this->db->lastInsertId();
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE USER
    |--------------------------------------------------------------------------
    */

    public function update(
        int $id,
        array $data
    ): bool {

        $fields = [];
        $params = [
            ':id' => $id
        ];

        foreach (
            ['name', 'email']
            as $column
        ) {

            if (
                array_key_exists(
                    $column,
                    $data
                )
            ) {

                $fields[] =
                    "{$column} = :{$column}";

                $params[":{$column}"] =
                    $data[$column];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql =
            "UPDATE users SET " .
            implode(', ', $fields) .
            " WHERE id = :id";

        $stmt =
            $this->db->prepare($sql);

        return $stmt->execute(
            $params
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PASSWORD
    |--------------------------------------------------------------------------
    */

    public function updatePassword(
        int $id,
        string $hashedPassword
    ): bool {

        $stmt =
            $this->db->prepare(
                "UPDATE users
                 SET password = :password
                 WHERE id = :id"
            );

        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id'       => $id
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE USER
    |--------------------------------------------------------------------------
    */

    public function delete(
        int $id
    ): bool {

        $stmt =
            $this->db->prepare(
                "DELETE FROM users
                 WHERE id = :id"
            );

        return $stmt->execute([
            ':id' => $id
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | FIND USER BY EMAIL
    |--------------------------------------------------------------------------
    */

    public function findByEmail(
        string $email
    ): array|false {

        $stmt =
            $this->db->prepare(
                "SELECT *
                 FROM users
                 WHERE email = :email"
            );

        $stmt->execute([
            ':email' => $email
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FIND USER BY ID
    |--------------------------------------------------------------------------
    */

    public function findById(
        int $id
    ): array|false {

        $stmt =
            $this->db->prepare(
                "SELECT *
                 FROM users
                 WHERE id = :id"
            );

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GET ALL USERS
    |--------------------------------------------------------------------------
    */

    public function findAll(): array
    {
        $stmt =
            $this->db->query(
                "SELECT *
                 FROM users
                 ORDER BY id DESC"
            );

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GET USERS BY ROLE
    |--------------------------------------------------------------------------
    */

    public function findAllByRole(
        string $roleName
    ): array {

        $stmt =
            $this->db->prepare(
                "SELECT u.*
                 FROM users u
                 INNER JOIN user_roles ur
                    ON ur.user_id = u.id
                 INNER JOIN roles r
                    ON r.id = ur.role_id
                 WHERE r.name = :role_name
                 ORDER BY u.id DESC"
            );

        $stmt->execute([
            ':role_name' => $roleName
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | COUNT USERS
    |--------------------------------------------------------------------------
    */

    public function count(): int
    {
        $stmt =
            $this->db->query(
                "SELECT COUNT(*)
                 FROM users"
            );

        return (int)
            $stmt->fetchColumn();
    }
}