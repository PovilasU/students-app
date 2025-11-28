<?php

use PHPUnit\Framework\TestCase;

class ApplicationServiceTest extends TestCase
{
    private PDO $pdo;
    private ApplicationRepository $repository;
    private ApplicationService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createApplicationsTable();

        $this->repository = new ApplicationRepository($this->pdo);
        $this->service = new ApplicationService($this->repository);
    }

    private function createApplicationsTable(): void
    {
        $sql = "
            CREATE TABLE applications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                type TEXT NOT NULL,
                status TEXT NOT NULL,
                rejection_comment TEXT DEFAULT NULL,
                created_at TEXT NOT NULL
            )
        ";
        $this->pdo->exec($sql);
    }

    private function insertApplication(
        int $studentId,
        string $title,
        string $description,
        string $type,
        string $status,
        ?string $rejectionComment = null
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO applications (student_id, title, description, type, status, rejection_comment, created_at)
            VALUES (:student_id, :title, :description, :type, :status, :rejection_comment, :created_at)
        ");

        $stmt->execute([
            ':student_id' => $studentId,
            ':title' => $title,
            ':description' => $description,
            ':type' => $type,
            ':status' => $status,
            ':rejection_comment' => $rejectionComment,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function testSubmitDraftForStudentStopsAfterThreeSubmittedOfSameType(): void
    {
        $studentId = 1;
        $type = 'stipendija';

        // jau yra 3 submitted to tipo
        $this->insertApplication($studentId, 'A1', 'Desc', $type, 'submitted');
        $this->insertApplication($studentId, 'A2', 'Desc', $type, 'submitted');
        $this->insertApplication($studentId, 'A3', 'Desc', $type, 'submitted');

        // dar vienas draft
        $draftId = $this->insertApplication($studentId, 'Draft', 'Desc', $type, 'draft');

        $error = $this->service->submitDraftForStudent($draftId, $studentId);

        $this->assertSame('Jau turite 3 pateiktas šio tipo paraiškas.', $error);

        $status = $this->getStatusById($draftId);
        $this->assertSame('draft', $status, 'Statusas neturėtų pasikeisti, kai viršyta limitas.');
    }

    public function testSubmitDraftForStudentWorksIfLessThanThree(): void
    {
        $studentId = 1;
        $type = 'stipendija';

        // tik 2 submitted
        $this->insertApplication($studentId, 'A1', 'Desc', $type, 'submitted');
        $this->insertApplication($studentId, 'A2', 'Desc', $type, 'submitted');

        $draftId = $this->insertApplication($studentId, 'Draft', 'Desc', $type, 'draft');

        $error = $this->service->submitDraftForStudent($draftId, $studentId);

        $this->assertNull($error);

        $status = $this->getStatusById($draftId);
        $this->assertSame('submitted', $status, 'Statusas turi pasikeisti į submitted, jei limitas neviršytas.');
    }

    public function testApproveSubmittedByAdminChangesStatusToApproved(): void
    {
        $studentId = 1;
        $type = 'stipendija';

        $id = $this->insertApplication($studentId, 'Test', 'Desc', $type, 'submitted');

        $this->service->approveSubmittedByAdmin($id);

        $status = $this->getStatusById($id);
        $this->assertSame('approved', $status, 'Patvirtinimas turi pakeisti statusą į approved.');
    }

    public function testRejectWithCommentChangesStatusToRejectedAndStoresComment(): void
    {
        $studentId = 1;
        $type = 'stipendija';

        $id = $this->insertApplication($studentId, 'Test', 'Desc', $type, 'submitted');

        $error = $this->service->rejectWithComment($id, 'Netinkami duomenys');

        $this->assertNull($error);

        $stmt = $this->pdo->prepare("
            SELECT status, rejection_comment
            FROM applications
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('rejected', $row['status']);
        $this->assertSame('Netinkami duomenys', $row['rejection_comment']);
    }

    private function getStatusById(int $id): ?string
    {
        $stmt = $this->pdo->prepare("SELECT status FROM applications WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $status = $stmt->fetchColumn();

        return $status !== false ? (string)$status : null;
    }
}
