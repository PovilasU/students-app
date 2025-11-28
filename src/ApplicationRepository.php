<?php

class ApplicationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findDraftForStudent(int $id, int $studentId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, student_id, title, description, type, status, created_at, rejection_comment
            FROM applications
            WHERE id = :id
              AND student_id = :student_id
              AND status = 'draft'
        ");
        $stmt->execute([
            ':id' => $id,
            ':student_id' => $studentId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function countSubmittedByTypeForStudent(int $studentId, string $type): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM applications
            WHERE student_id = :student_id
              AND type = :type
              AND status = 'submitted'
        ");
        $stmt->execute([
            ':student_id' => $studentId,
            ':type' => $type,
        ]);

        return (int)$stmt->fetchColumn();
    }

    public function submitDraft(int $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE applications
            SET status = 'submitted'
            WHERE id = :id
              AND status = 'draft'
        ");
        $stmt->execute([
            ':id' => $id,
        ]);
    }

    public function createDraft(int $studentId, string $title, string $description, string $type): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO applications (student_id, title, description, type, status, created_at)
            VALUES (:student_id, :title, :description, :type, :status, :created_at)
        ");

        $stmt->execute([
            ':student_id' => $studentId,
            ':title' => $title,
            ':description' => $description,
            ':type' => $type,
            ':status' => 'draft',
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getStudentApplications(int $studentId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, title, type, status, created_at, rejection_comment
            FROM applications
            WHERE student_id = :student_id
            ORDER BY created_at DESC
        ");
        $stmt->execute([':student_id' => $studentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllApplications(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, title, type, status, created_at, rejection_comment
            FROM applications
            ORDER BY created_at DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function approveSubmitted(int $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE applications
            SET status = 'approved'
            WHERE id = :id
              AND status = 'submitted'
        ");
        $stmt->execute([
            ':id' => $id,
        ]);
    }
}
