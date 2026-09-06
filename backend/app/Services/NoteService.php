<?php
require_once __DIR__ . '/../Repositories/NoteRepository.php';
require_once __DIR__ . '/../Security/AES.php';
class NoteService
{
    private NoteRepository $noteRepository;
    public function __construct(PDO $pdo)
    {
        $this->noteRepository = new NoteRepository($pdo);
    }
    // ========================================
    // Create Note
    // ========================================
    public function createNote(
        int $appointmentId,
        ?int $userId,
        string $note
    ): int {
        if ($appointmentId <= 0) {
            throw new Exception('Valid appointment ID is required');
        }
        if (empty(trim($note))) {
            throw new Exception('Note is required');
        }
        $aesKey = $_ENV['AES_KEY'] ?? '';
        if (empty($aesKey)) {
            throw new Exception('AES key is not configured');
        }
        $encryptedNote = AES::encrypt(
            $note,
            $aesKey
        );
        return $this->noteRepository->createNote(
            $appointmentId,
            $userId,
            $encryptedNote
        );
    }
    // ========================================
    // Get All Notes
    // ========================================
    public function getAllNotes(): array
    {
        $notes = $this->noteRepository->getAll();
        $aesKey = $_ENV['AES_KEY'] ?? '';
        foreach ($notes as &$note) {
            if (!empty($note['note'])) {
                $decryptedNote = AES::decrypt(
                    $note['note'],
                    $aesKey
                );
                $note['note'] = $decryptedNote !== false
                    ? $decryptedNote
                    : null;
            }
        }
        return $notes;
    }
    // ========================================
    // Get Note By ID
    // ========================================
    public function getNoteById(int $noteId): array
    {
        if ($noteId <= 0) {
            throw new Exception('Invalid note ID');
        }
        $note = $this->noteRepository->getById($noteId);
        if (!$note) {
            throw new Exception('Note not found');
        }
        $aesKey = $_ENV['AES_KEY'] ?? '';
        if (!empty($note['note'])) {
            $decryptedNote = AES::decrypt(
                $note['note'],
                $aesKey
            );
            $note['note'] = $decryptedNote !== false
                ? $decryptedNote
                : null;
        }
        return $note;
    }
    // ========================================
    // Get Notes By Appointment ID
    // ========================================
    public function getNotesByAppointmentId(
        int $appointmentId
    ): array {
        if ($appointmentId <= 0) {
            throw new Exception('Invalid appointment ID');
        }
        $notes = $this->noteRepository->getByAppointmentId(
            $appointmentId
        );
        $aesKey = $_ENV['AES_KEY'] ?? '';
        foreach ($notes as &$note) {
            if (!empty($note['note'])) {
                $decryptedNote = AES::decrypt(
                    $note['note'],
                    $aesKey
                );
                $note['note'] = $decryptedNote !== false
                    ? $decryptedNote
                    : null;
            }
        }
        return $notes;
    }
    // ========================================
    // Update Note
    // ========================================
    public function updateNote(
        int $noteId,
        string $note
    ): bool {
        if ($noteId <= 0) {
            throw new Exception('Invalid note ID');
        }
        if (empty(trim($note))) {
            throw new Exception('Note is required');
        }
        $existingNote = $this->noteRepository->getById($noteId);
        if (!$existingNote) {
            throw new Exception('Note not found');
        }
        $aesKey = $_ENV['AES_KEY'] ?? '';
        if (empty($aesKey)) {
            throw new Exception('AES key is not configured');
        }
        $encryptedNote = AES::encrypt(
            $note,
            $aesKey
        );
        return $this->noteRepository->updateNote(
            $noteId,
            $encryptedNote
        );
    }
    // ========================================
    // Delete Note
    // ========================================
    public function deleteNote(int $noteId): bool
    {
        if ($noteId <= 0) {
            throw new Exception('Invalid note ID');
        }
        $note = $this->noteRepository->getById($noteId);
        if (!$note) {
            throw new Exception('Note not found');
        }
        return $this->noteRepository->deleteNote($noteId);
    }
}