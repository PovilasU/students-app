<?php

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dbDir = __DIR__ . '/../data';

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }

        $dbPath = $dbDir . '/app.sqlite';
        $dsn = 'sqlite:' . $dbPath;

        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    return $pdo;
}

function initDatabase(): void
{
    getPDO();
}

function initUsersTable(): void
{
    $pdo = getPDO();

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

    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    if ($count === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password_hash, role)
            VALUES (:name, :email, :password_hash, :role)
        ");

        $stmt->execute([
            ':name' => 'Student User',
            ':email' => 'student@example.com',
            ':password_hash' => password_hash('student123', PASSWORD_DEFAULT),
            ':role' => 'student',
        ]);

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
            rejection_comment TEXT DEFAULT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY (student_id) REFERENCES users(id)
        )
    ";
    $pdo->exec($sql);

    try {
        $pdo->exec("ALTER TABLE applications ADD COLUMN rejection_comment TEXT");
    } catch (PDOException $e) {
        // ignore if already exists
    }
}

function findUserByEmail(string $email): ?array
{
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function findUserById(int $id): ?array
{
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT id, name, role, email FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function createUser(string $name, string $email, string $passwordHash, string $role = 'student'): bool
{
    $pdo = getPDO();

    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, role)
        VALUES (:name, :email, :password_hash, :role)
    ");

    try {
        return $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':role' => $role,
        ]);
    } catch (PDOException $e) {
        return false;
    }
}
