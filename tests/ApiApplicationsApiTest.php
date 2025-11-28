<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/ApiLogin.php';
require_once __DIR__ . '/../src/ApiApplications.php';
require_once __DIR__ . '/../src/db.php';

class ApiApplicationsApiTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];

        initDatabase();
        initUsersTable();
        initApplicationsTable();

        $pdo = getPDO();
        $pdo->exec("DELETE FROM applications");
    }

    private function loginStudent(): void
    {
        $body = json_encode([
            'email' => 'student@example.com',
            'password' => 'student123',
        ], JSON_UNESCAPED_UNICODE);

        api_login_handle('POST', $body);
    }

    private function loginAdmin(): void
    {
        $body = json_encode([
            'email' => 'admin@example.com',
            'password' => 'admin123',
        ], JSON_UNESCAPED_UNICODE);

        api_login_handle('POST', $body);
    }

    public function testGetApplicationsRequiresAuth(): void
    {
        [$status, $data] = api_applications_handle('GET', [], null);

        $this->assertSame(401, $status);
        $this->assertFalse($data['success']);
    }

    public function testStudentCanCreateDraftAndListIt(): void
    {
        $this->loginStudent();

        // pradžioje sąrašas tuščias
        [$status, $data] = api_applications_handle('GET', [], null);
        $this->assertSame(200, $status);
        $this->assertIsArray($data);
        $this->assertCount(0, $data);

        // sukuriam ruošinį
        $body = json_encode([
            'title' => 'API paraiška',
            'description' => 'Aprašymas',
            'type' => 'Stipendija',
        ], JSON_UNESCAPED_UNICODE);

        [$statusCreate, $dataCreate] = api_applications_handle('POST', [], $body);
        $this->assertSame(201, $statusCreate);
        $this->assertTrue($dataCreate['success']);

        // vėl gaunam sąrašą – turi būti 1
        [$status2, $data2] = api_applications_handle('GET', [], null);
        $this->assertSame(200, $status2);
        $this->assertCount(1, $data2);
    }

    public function testAdminSeesAllApplications(): void
    {
        $this->loginStudent();

        $body = json_encode([
            'title' => 'API paraiška',
            'description' => 'Aprašymas',
            'type' => 'Stipendija',
        ], JSON_UNESCAPED_UNICODE);

        api_applications_handle('POST', [], $body);

        // adminas
        $_SESSION = [];
        $this->loginAdmin();

        [$status, $data] = api_applications_handle('GET', [], null);

        $this->assertSame(200, $status);
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(1, count($data));
    }
}
