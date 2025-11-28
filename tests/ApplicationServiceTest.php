<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/ApplicationRepository.php';
require_once __DIR__ . '/../src/ApplicationService.php';

class ApplicationServiceTest extends TestCase
{
    private PDO $pdo;
    private ApplicationRepository $repository;
    private ApplicationService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createUsersTable();
        $this->createApplicationsTable();

        $this->repository = new ApplicationRepository($this->pdo);
        $this->service = new ApplicationService($this->repository);
    }

    private function createUsersTable(): void
    {
        $sql = "
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL
            )
        ";
        $this->pdo->exec($sql);
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
                created_at TEXT NOT NULL,
                FOREIGN KEY (student_id) REFERENCES users(id)
            )
        ";
        $this->pdo->exec($sql);
    }

    private function insertUser(string $name, string $role = 'student'): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, email, password_hash, role)
            VALUES (:name, :email, :password_hash, :role)
        ");

        $stmt->execute([
            ':name' => $name,
            ':email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
            ':password_hash' => password_hash('secret', PASSWORD_DEFAULT),
            ':role' => $role,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function insertApplication(
        int $studentId,
        string $title,
        string $description,
        string $type,
        string $status = 'draft'
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO applications (student_id, title, description, type, status, created_at)
            VALUES (:student_id, :title, :description, :type, :status, :created_at)
        ");

        $stmt->execute([
            ':student_id' => $studentId,
            ':title' => $title,
            ':description' => $description,
            ':type' => $type,
            ':status' => $status,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function getStatusById(int $id): ?string
    {
        $stmt = $this->pdo->prepare("SELECT status FROM applications WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $value = $stmt->fetchColumn();

        return $value !== false ? (string) $value : null;
    }

    private function getRejectionCommentById(int $id): ?string
    {
        $stmt = $this->pdo->prepare("SELECT rejection_comment FROM applications WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $value = $stmt->fetchColumn();

        return $value !== false ? (string) $value : null;
    }

    public function testCreateDraftForStudentCreatesRow(): void
    {
        $studentId = $this->insertUser('Student One');

        $error = $this->service->createDraftForStudent(
            $studentId,
            'Test paraiška',
            'Aprašymas',
            'Tipas A'
        );

        $this->assertNull($error);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM applications");
        $this->assertSame(1, (int) $stmt->fetchColumn());

        $stmt = $this->pdo->query("SELECT * FROM applications LIMIT 1");
        $row = $stmt->fetch();

        $this->assertSame($studentId, (int) $row['student_id']);
        $this->assertSame('draft', $row['status']);
        $this->assertSame('Test paraiška', $row['title']);
    }

    public function testCreateDraftForStudentValidatesRequiredFields(): void
    {
        $studentId = $this->insertUser('Student One');

        $error = $this->service->createDraftForStudent(
            $studentId,
            '',
            'Aprašymas',
            'Tipas A'
        );

        $this->assertNotNull($error);
        $this->assertStringContainsString('Visi laukai yra privalomi', $error);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM applications");
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }

    public function testCreateDraftForStudentRespectsRateLimit(): void
    {
        $studentId = $this->insertUser('Student One');

        for ($i = 0; $i < 5; $i++) {
            $err = $this->service->createDraftForStudent(
                $studentId,
                'Pavadinimas ' . $i,
                'Aprašymas',
                'Tipas A'
            );
            $this->assertNull($err);
        }

        $error = $this->service->createDraftForStudent(
            $studentId,
            'Per daug',
            'Aprašymas',
            'Tipas A'
        );

        $this->assertNotNull($error);
        $this->assertStringContainsString('Per daug bandymų sukurti paraiškas', $error);
    }

    public function testSubmitDraftForStudentChangesStatusToSubmitted(): void
    {
        $studentId = $this->insertUser('Student One');
        $appId = $this->insertApplication(
            $studentId,
            'Test paraiška',
            'Aprašymas',
            'Tipas A',
            'draft'
        );

        $error = $this->service->submitDraftForStudent($appId, $studentId);

        $this->assertNull($error);
        $this->assertSame('submitted', $this->getStatusById($appId));
    }

    public function testSubmitDraftForStudentRejectsOtherStudentApplication(): void
    {
        $studentOne = $this->insertUser('Student One');
        $studentTwo = $this->insertUser('Student Two');

        $appId = $this->insertApplication(
            $studentOne,
            'Test paraiška',
            'Aprašymas',
            'Tipas A',
            'draft'
        );

        $error = $this->service->submitDraftForStudent($appId, $studentTwo);

        $this->assertNotNull($error);
        $this->assertStringContainsString('Negalite pateikti kito studento paraiškos', $error);
        $this->assertSame('draft', $this->getStatusById($appId));
    }

    public function testSubmitDraftForStudentHonorsMaxThreeRule(): void
    {
        $studentId = $this->insertUser('Student One');

        for ($i = 0; $i < 3; $i++) {
            $this->insertApplication(
                $studentId,
                'Paraiška ' . $i,
                'Aprašymas',
                'Tipas A',
                'submitted'
            );
        }

        $draftId = $this->insertApplication(
            $studentId,
            'Ruošinys',
            'Aprašymas',
            'Tipas A',
            'draft'
        );

        $error = $this->service->submitDraftForStudent($draftId, $studentId);

        $this->assertNotNull($error);
        $this->assertStringContainsString('Jau turite 3 pateiktas šio tipo paraiškas', $error);
        $this->assertSame('draft', $this->getStatusById($draftId));
    }

    public function testApproveSubmittedByAdminChangesStatusToApproved(): void
    {
        $studentId = $this->insertUser('Student One');
        $appId = $this->insertApplication(
            $studentId,
            'Test paraiška',
            'Aprašymas',
            'Tipas A',
            'submitted'
        );

        $this->service->approveSubmittedByAdmin($appId);

        $this->assertSame('approved', $this->getStatusById($appId));
    }

    public function testApproveSubmittedByAdminIgnoresNonSubmitted(): void
    {
        $studentId = $this->insertUser('Student One');
        $appId = $this->insertApplication(
            $studentId,
            'Test paraiška',
            'Aprašymas',
            'Tipas A',
            'draft'
        );

        $this->service->approveSubmittedByAdmin($appId);

        $this->assertSame('draft', $this->getStatusById($appId));
    }

    public function testRejectWithCommentChangesStatusAndStoresComment(): void
    {
        $studentId = $this->insertUser('Student One');
        $appId = $this->insertApplication(
            $studentId,
            'Test paraiška',
            'Aprašymas',
            'Tipas A',
            'submitted'
        );

        $error = $this->service->rejectWithComment($appId, 'Netinkami duomenys');

        $this->assertNull($error);
        $this->assertSame('rejected', $this->getStatusById($appId));
        $this->assertSame('Netinkami duomenys', $this->getRejectionCommentById($appId));
    }

    public function testRejectWithCommentRequiresNonEmptyComment(): void
    {
        $studentId = $this->insertUser('Student One');
        $appId = $this->insertApplication(
            $studentId,
            'Test paraiška',
            'Aprašymas',
            'Tipas A',
            'submitted'
        );

        $error = $this->service->rejectWithComment($appId, '   ');

        $this->assertNotNull($error);
        $this->assertStringContainsString('Prašome įrašyti atmetimo komentarą', $error);
        $this->assertSame('submitted', $this->getStatusById($appId));
    }
}
