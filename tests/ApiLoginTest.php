<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/ApiLogin.php';
require_once __DIR__ . '/../src/db.php';

class ApiLoginTest extends TestCase
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
    }

    public function testLoginSuccess(): void
    {
        $body = json_encode([
            'email' => 'student@example.com',
            'password' => 'student123',
        ], JSON_UNESCAPED_UNICODE);

        [$status, $data] = api_login_handle('POST', $body);

        $this->assertSame(200, $status);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('user', $data);
        $this->assertSame('student', $data['user']['role']);
        $this->assertArrayHasKey('user_id', $_SESSION);
    }

    public function testLoginWrongPassword(): void
    {
        $body = json_encode([
            'email' => 'student@example.com',
            'password' => 'wrong',
        ], JSON_UNESCAPED_UNICODE);

        [$status, $data] = api_login_handle('POST', $body);

        $this->assertSame(401, $status);
        $this->assertFalse($data['success']);
    }
}
