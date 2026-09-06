<?php
require_once __DIR__ . '/../Services/NoteService.php';
class NoteController
{
    private NoteService $noteService;
    public function __construct(PDO $pdo)
    {
        $this->noteService = new NoteService($pdo);
    }
    // ========================================
    // Create Note
    // ========================================
    public function create(): array
    {
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );
        if (!is_array($input)) {
            throw new Exception('Invalid JSON request');
        }
        $appointmentId = $input['appointment_id'] ?? 0;
        $userId = $input['user_id'] ?? null;
        $note = $input['note'] ?? '';
        $noteId = $this->noteService->createNote(
            (int)$appointmentId,
            $userId !== null ? (int)$userId : null,
            $note
        );
        return [
            'message' => 'Note created successfully',
            'note_id' => $noteId
        ];
    }
    // ========================================
    // Get All Notes
    // ========================================
    public function getAll(): array
    {
        $notes = $this->noteService->getAllNotes();
        return [
            'message' => 'Notes fetched successfully',
            'data' => $notes
        ];
    }
    // ========================================
    // Get Note By ID
    // ========================================
    public function getById(int $noteId): array
    {
        $note = $this->noteService->getNoteById($noteId);
        return [
            'message' => 'Note fetched successfully',
            'data' => $note
        ];
    }
    // ========================================
    // Get Notes By Appointment ID
    // ========================================
    public function getByAppointmentId(
        int $appointmentId
    ): array {
        $notes = $this->noteService->getNotesByAppointmentId(
            $appointmentId
        );
        return [
            'message' => 'Appointment notes fetched successfully',
            'data' => $notes
        ];
    }
    // ========================================
    // Update Note
    // ========================================
    public function update(int $noteId): array
    {
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );
        if (!is_array($input)) {
            throw new Exception('Invalid JSON request');
        }
        $note = $input['note'] ?? '';
        $this->noteService->updateNote(
            $noteId,
            $note
        );
        return [
            'message' => 'Note updated successfully'
        ];
    }
    // ========================================
    // Delete Note
    // ========================================
    public function delete(int $noteId): array
    {
        $this->noteService->deleteNote($noteId);
        return [
            'message' => 'Note deleted successfully'
        ];
    }
}