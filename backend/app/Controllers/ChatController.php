<?php

require_once __DIR__ . '/../Services/ChatService.php';

class ChatController
{
    private ChatService $chatService;

    public function __construct(PDO $pdo)
    {
        $this->chatService = new ChatService($pdo);
    }

    public function send(array $input, int $senderUserId): array
    {
        $receiverUserId = $input['receiver_user_id'] ?? 0;
        $message = $input['message'] ?? '';

        $messageId = $this->chatService->sendMessage(
            $senderUserId,
            (int) $receiverUserId,
            (string) $message
        );

        return [
            'message' => 'Message sent successfully',
            'message_id' => $messageId
        ];
    }

    public function conversation(int $loggedInUserId, int $otherUserId): array
    {
        $messages = $this->chatService->getConversation(
            $loggedInUserId,
            $otherUserId
        );

        return [
            'message' => 'Conversation fetched successfully',
            'data' => $messages
        ];
    }

    public function users(int $loggedInUserId): array
    {
        $users = $this->chatService->getAvailableUsers($loggedInUserId);

        return [
            'message' => 'Users fetched successfully',
            'data' => $users
        ];
    }

    public function delete(array $input, int $loggedInUserId): array
    {
        $messageId  = (int) ($input['message_id'] ?? 0);
        $deleteType = $input['delete_type'] ?? 'me'; // "me" or "everyone"

        if ($messageId <= 0) {
            throw new Exception('Valid message ID is required');
        }

        if (!in_array($deleteType, ['me', 'everyone'], true)) {
            throw new Exception('Invalid delete type');
        }

        return $this->chatService->deleteMessage(
            $loggedInUserId,
            $messageId,
            $deleteType
        );
    }
}