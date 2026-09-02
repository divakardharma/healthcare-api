<?php

require_once __DIR__ . '/../Config/database.php';

class UserRepository
{
    private mysqli $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }
 
  //  ----------------------------------------        findByEmail          ------------------------------------------

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1" );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc() ?: false;
    }

  //  -----------------------------------------        findByID           ----------------------------------------

    public function findById(int $userId): array|false
   {
        $stmt = $this->conn->prepare( "SELECT *  FROM users  WHERE id = ?  LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();  

        return $result->fetch_assoc() ?: false;
    }


   //   ---------------------------------------           Create          ------------------------------------------ 

    public function create(int $tenantId, string $name, string $email, string $password): int {

        $stmt = $this->conn->prepare( "INSERT INTO users (tenant_id, name, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param( "isss", $tenantId, $name, $email, $password );
        $stmt->execute();

        return $this->conn->insert_id;
    }
   
    //  -------------------------------------------     UpdatePassword      ------------------------------------------- 
    
    
    public function updatePassword(int $userId,string $hashedPassword): bool 
    {
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si",$hashedPassword,$userId);

        return $stmt->execute();
}
}