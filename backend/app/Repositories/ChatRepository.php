<?php

require_once __DIR__ . '/../Security/AES.php';

class ChatRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ========================================
    // Create Message
    // ========================================
    public function createMessage(
        int $senderUserId,
        int $receiverUserId,
        string $message
    ): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO chat_messages
            (sender_user_id, receiver_user_id, message)
            VALUES (?, ?, ?)"
        );

        $stmt->execute([
            $senderUserId,
            $receiverUserId,
            $message
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    // ========================================
    // Get Conversation Between Two Users
    // ========================================
    public function getConversation(
        int $userOneId,
        int $userTwoId
    ): array {
        $stmt = $this->pdo->prepare(
            "SELECT
                cm.id,
                cm.sender_user_id,
                cm.receiver_user_id,
                cm.message,
                cm.created_at,
                cm.deleted_for_sender,
                cm.deleted_for_receiver,
                cm.is_deleted_for_everyone,
                u.name AS sender_name
            FROM chat_messages cm
            INNER JOIN users u
                ON u.id = cm.sender_user_id
            WHERE
                (
                    (cm.sender_user_id = ? AND cm.receiver_user_id = ?)
                    OR
                    (cm.sender_user_id = ? AND cm.receiver_user_id = ?)
                )
                AND NOT (
                    (cm.sender_user_id = ? AND cm.deleted_for_sender = 1)
                    OR
                    (cm.receiver_user_id = ? AND cm.deleted_for_receiver = 1)
                )
            ORDER BY cm.created_at ASC"
        );

        $stmt->execute([
            $userOneId,
            $userTwoId,
            $userTwoId,
            $userOneId,
            $userOneId,
            $userOneId
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decrypt sender_name
        foreach ($rows as &$row) {
            if (isset($row['sender_name'])) {
                $row['sender_name'] = AES::decryptField($row['sender_name']);
            }
        }

        return $rows;
    }

    // ========================================
    // Get single message by ID
    // ========================================
    public function getMessageById(int $messageId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM chat_messages WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$messageId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    // ========================================
    // Soft Delete for Me
    // ========================================
    public function deleteForMe(int $messageId, int $userId): bool
    {
        $message = $this->getMessageById($messageId);

        if (!$message) {
            return false;
        }

        if ((int) $message['sender_user_id'] === $userId) {
            $stmt = $this->pdo->prepare(
                "UPDATE chat_messages 
                 SET deleted_for_sender = 1 
                 WHERE id = ?"
            );
        } elseif ((int) $message['receiver_user_id'] === $userId) {
            $stmt = $this->pdo->prepare(
                "UPDATE chat_messages 
                 SET deleted_for_receiver = 1 
                 WHERE id = ?"
            );
        } else {
            return false;
        }

        return $stmt->execute([$messageId]);
    }

    // ========================================
    // Soft Delete for Everyone (only sender)
    // ========================================
    public function deleteForEveryone(int $messageId, int $senderUserId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE chat_messages 
             SET is_deleted_for_everyone = 1 
             WHERE id = ? AND sender_user_id = ?"
        );

        return $stmt->execute([$messageId, $senderUserId]);
    }

    // ========================================
    // Get Available Users
    // ========================================
    public function getAvailableUsers(int $loggedInUserId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                u.id,
                u.name,
                GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ',') AS roles
            FROM users u
            INNER JOIN staff s
                ON s.user_id = u.id
                AND s.status = 'Active'
            LEFT JOIN user_roles ur
                ON ur.user_id = u.id
            LEFT JOIN roles r
                ON r.id = ur.role_id
            WHERE u.id != ?
            GROUP BY u.id, u.name
            ORDER BY u.id ASC"
        );

        $stmt->execute([$loggedInUserId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        foreach ($rows as $row) {
            $name = isset($row['name'])
                ? AES::decryptField($row['name'])
                : null;

            $roles = [];
            if (!empty($row['roles'])) {
                $roles = array_values(
                    array_filter(
                        array_map('trim', explode(',', $row['roles']))
                    )
                );
            }

            $result[] = [
                'id'    => (int) $row['id'],
                'name'  => $name,
                'roles' => $roles
            ];
        }

        return $result;
    }

    // ========================================
    // Check User Exists
    // ========================================
    public function userExists(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM users WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$userId]);

        return (bool) $stmt->fetchColumn();
    }
}