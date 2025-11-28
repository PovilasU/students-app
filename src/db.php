<?php

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dbPath = __DIR__ . '/../data/app.sqlite';
        $dsn = 'sqlite:' . $dbPath;

        try {
            $pdo = new PDO($dsn);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('DB error: ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}

function initDatabase(): void
{
    $pdo = getPDO();

    $sql = "
        CREATE TABLE IF NOT EXISTS test (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at TEXT NOT NULL
        )
    ";

    $pdo->exec($sql);
}

function initUsersTable(): void
{
    $pdo = getPDO();

    // Create table with email + password_hash
    $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL
        )
    ";
    $pdo->exec($sql);

    // Check if empty
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    if ($count === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password_hash, role)
            VALUES (:name, :email, :password_hash, :role)
        ");

        // Student User
        $stmt->execute([
            ':name' => 'Student User',
            ':email' => 'student@example.com',
            ':password_hash' => password_hash('student123', PASSWORD_DEFAULT),
            ':role' => 'student',
        ]);

        // Admin User
        $stmt->execute([
            ':name' => 'Admin User',
            ':email' => 'admin@example.com',
            ':password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            ':role' => 'admin',
        ]);
    }
}


function initApplicationsTable(): void
{
    $pdo = getPDO();

    $sql = "
        CREATE TABLE IF NOT EXISTS applications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            type TEXT NOT NULL,
            status TEXT NOT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY (student_id) REFERENCES users(id)
        )
    ";

    $pdo->exec($sql);

    try {
        // add column if not exists
        $pdo->exec("ALTER TABLE applications ADD COLUMN rejection_comment TEXT");
    } catch (PDOException $e) {
        // ignore
    }
}


function findUserById(int $id): ?array
{
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}

function getAllUsers(): array
{
    $pdo = getPDO();

    $stmt = $pdo->query("SELECT id, name, role FROM users ORDER BY id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
