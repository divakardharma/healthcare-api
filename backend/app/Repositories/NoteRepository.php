<?php
require_once __DIR__ . '/../Config/database.php';
class NoteRepository
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    // ========================================
    // Create Note
    // ========================================
    public function createNote(
        int $appointmentId,
        ?int $userId,
        string $note
    ): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO appointment_notes
            (appointment_id, user_id, note)
            VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $appointmentId,
            $userId,
            $note
        ]);
        return (int)$this->pdo->lastInsertId();
    }
    // ========================================
    // Get All Notes
    // ========================================
    public function getAll(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM appointment_notes ORDER BY id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
    // ========================================
    // Get Note By ID
    // ========================================
    public function getById(int $noteId): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM appointment_notes
            WHERE id = ?
            LIMIT 1"
        );
        $stmt->execute([$noteId]);
        return $stmt->fetch() ?: false;
    }
    // ========================================
    // Get Notes By Appointment ID
    // ========================================
    public function getByAppointmentId(int $appointmentId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM appointment_notes
            WHERE appointment_id = ?
            ORDER BY id DESC"
        );
        $stmt->execute([$appointmentId]);
        return $stmt->fetchAll();
    }
    // ========================================
    // Update Note
    // ========================================
    public function updateNote(
        int $noteId,
        string $note
    ): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE appointment_notes
            SET note = ?
            WHERE id = ?"
        );
        return $stmt->execute([
            $note,
            $noteId
        ]);
    }
    // ========================================
    // Delete Note
    // ========================================
    public function deleteNote(int $noteId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM appointment_notes
            WHERE id = ?"
        );
        return $stmt->execute([$noteId]);
    }
}