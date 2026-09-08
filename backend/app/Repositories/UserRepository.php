<?php

require_once __DIR__ . '/../Security/AES.php';

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
                (name, email, email_hash, password)
                VALUES
                (:name, :email, :email_hash, :password)";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':name'       => AES::encryptField($data['name']),
            ':email'      => AES::encryptField($data['email']),
            ':email_hash' => AES::searchHash($data['email']),
            // password arrives already bcrypt-hashed via Hash::make()
            ':password'   => $data['password']
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
                    AES::encryptField($data[$column]);

                // Keep the lookup hash in sync whenever email changes
                if ($column === 'email') {

                    $fields[] = "email_hash = :email_hash";

                    $params[':email_hash'] =
                        AES::searchHash($data['email']);
                }
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
                 WHERE email_hash = :email_hash"
            );

        $stmt->execute([
            ':email_hash' => AES::searchHash($email)
        ]);

        $user = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $user === false
            ? false
            : $this->decryptRow($user);
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

        $user = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $user === false
            ? false
            : $this->decryptRow($user);
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

        return array_map(
            [$this, 'decryptRow'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
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

        return array_map(
            [$this, 'decryptRow'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
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


    /*
    |--------------------------------------------------------------------------
    | DECRYPT ROW
    |--------------------------------------------------------------------------
    | name/email are stored AES encrypted (see create()/update() above).
    | Every read path routes through here so callers keep receiving
    | plaintext, unchanged from before this migration.
    |--------------------------------------------------------------------------
    */
    private function decryptRow(array $user): array
    {
        if (isset($user['name'])) {
            $user['name'] = AES::decryptField($user['name']);
        }

        if (isset($user['email'])) {
            $user['email'] = AES::decryptField($user['email']);
        }

        // Internal lookup value, never expose it in API responses
        unset($user['email_hash']);

        return $user;
    }
}