<?php
// db.php

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // DB failas bus students-app/app.sqlite
        $dbPath = __DIR__ . '/app.sqlite';

        $dsn = 'sqlite:' . $dbPath;

        try {
            $pdo = new PDO($dsn);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // kol kas rodome klaidą, kad matytume kas blogai
            die('Nepavyko prisijungti prie DB: ' . htmlspecialchars($e->getMessage()));
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
