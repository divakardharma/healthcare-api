<?php

require_once __DIR__ . '/../Repositories/ChatRepository.php';
require_once __DIR__ . '/../Security/AES.php';

class ChatService
{
    private ChatRepository $chatRepository;

    public function __construct(PDO $pdo)
    {
        $this->chatRepository = new ChatRepository($pdo);
    }

    // ========================================
    // Send Message
    // ========================================
    public function sendMessage(
        int $senderUserId,
        int $receiverUserId,
        string $message
    ): int {
        if ($senderUserId <= 0) {
            throw new Exception('Valid sender is required');
        }

        if ($receiverUserId <= 0) {
            throw new Exception('Valid receiver user ID is required');
        }

        if ($receiverUserId === $senderUserId) {
            throw new Exception('You cannot send a message to yourself');
        }

        if (empty(trim($message))) {
            throw new Exception('Message is required');
        }

        if (!$this->chatRepository->userExists($receiverUserId)) {
            throw new Exception('Receiver user does not exist');
        }

        $aesKey = $_ENV['AES_KEY'] ?? '';

        if (empty($aesKey)) {
            throw new Exception('AES key is not configured');
        }

        $encryptedMessage = AES::encrypt($message, $aesKey);

        return $this->chatRepository->createMessage(
            $senderUserId,
            $receiverUserId,
            $encryptedMessage
        );
    }

    // ========================================
    // Get Conversation
    // ========================================
    public function getConversation(
        int $loggedInUserId,
        int $otherUserId
    ): array {
        if ($loggedInUserId <= 0) {
            throw new Exception('Invalid authenticated user');
        }

        if ($otherUserId <= 0) {
            throw new Exception('Invalid user ID');
        }

        if ($otherUserId === $loggedInUserId) {
            throw new Exception('Cannot fetch conversation with yourself');
        }

        if (!$this->chatRepository->userExists($otherUserId)) {
            throw new Exception('User not found');
        }

        $messages = $this->chatRepository->getConversation(
            $loggedInUserId,
            $otherUserId
        );

        $aesKey = $_ENV['AES_KEY'] ?? '';

        foreach ($messages as &$message) {
            if (!empty($message['is_deleted_for_everyone'])) {
                $message['message'] = 'This message was deleted';
                $message['is_deleted'] = true;
            } else {
                if (!empty($message['message'])) {
                    $decryptedMessage = AES::decrypt(
                        $message['message'],
                        $aesKey
                    );

                    $message['message'] = $decryptedMessage !== false
                        ? $decryptedMessage
                        : null;
                }
                $message['is_deleted'] = false;
            }
        }

        return $messages;
    }

    // ========================================
    // Soft Delete Message
    // ========================================
    public function deleteMessage(
        int $loggedInUserId,
        int $messageId,
        string $deleteType
    ): array {
        $message = $this->chatRepository->getMessageById($messageId);

        if (!$message) {
            throw new Exception('Message not found');
        }

        $isSender   = (int) $message['sender_user_id'] === $loggedInUserId;
        $isReceiver = (int) $message['receiver_user_id'] === $loggedInUserId;

        if (!$isSender && !$isReceiver) {
            throw new Exception('You are not allowed to delete this message');
        }

        if ($deleteType === 'everyone') {
            if (!$isSender) {
                throw new Exception('Only the sender can delete for everyone');
            }

            $this->chatRepository->deleteForEveryone($messageId, $loggedInUserId);

            return ['message' => 'Message deleted for everyone'];
        }

        // delete for me
        $this->chatRepository->deleteForMe($messageId, $loggedInUserId);

        return ['message' => 'Message deleted for you'];
    }

    // ========================================
    // Get Available Users
    // ========================================
    public function getAvailableUsers(int $loggedInUserId): array
    {
        if ($loggedInUserId <= 0) {
            throw new Exception('Invalid authenticated user');
        }

        return $this->chatRepository->getAvailableUsers($loggedInUserId);
    }
}