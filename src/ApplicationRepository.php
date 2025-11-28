<?php

class ApplicationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAllForStudent(int $studentId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM applications
            WHERE student_id = :sid
            ORDER BY created_at DESC
        ");
        $stmt->execute([':sid' => $studentId]);

        return $stmt->fetchAll();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT a.*, u.name AS student_name
            FROM applications a
            JOIN users u ON a.student_id = u.id
            ORDER BY a.created_at DESC
        ");

        return $stmt->fetchAll();
    }

    public function insertDraft(int $studentId, string $title, string $description, string $type): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO applications (student_id, title, description, type, status, created_at)
            VALUES (:student_id, :title, :description, :type, 'draft', :created_at)
        ");

        $stmt->execute([
            ':student_id' => $studentId,
            ':title' => $title,
            ':description' => $description,
            ':type' => $type,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM applications
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateDraft(int $id, string $title, string $description, string $type): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE applications
            SET title = :title,
                description = :description,
                type = :type
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':description' => $description,
            ':type' => $type,
        ]);
    }

    public function countSubmittedByTypeForStudent(int $studentId, string $type): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM applications
            WHERE student_id = :sid
              AND type = :type
              AND status = 'submitted'
        ");
        $stmt->execute([
            ':sid' => $studentId,
            ':type' => $type,
        ]);

        return (int)$stmt->fetchColumn();
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE applications
            SET status = :status
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $id,
            ':status' => $status,
        ]);
    }

    public function updateStatusAndComment(int $id, string $status, string $comment): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE applications
            SET status = :status,
                rejection_comment = :comment
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':comment' => $comment,
        ]);
    }

    public function countRecentDraftsForStudent(int $studentId, int $seconds): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM applications
            WHERE student_id = :sid
              AND created_at >= :since
        ");
        $stmt->execute([
            ':sid' => $studentId,
            ':since' => date('Y-m-d H:i:s', time() - $seconds),
        ]);

        return (int)$stmt->fetchColumn();
    }
}
