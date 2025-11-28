<?php
// public/api/applications.php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/ApplicationRepository.php';
require __DIR__ . '/../../src/ApplicationService.php';

initDatabase();
initUsersTable();
initApplicationsTable();

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function current_user_api(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return findUserById((int)$_SESSION['user_id']);
}

$user = current_user_api();
if ($user === null) {
    json_response([
        'success' => false,
        'error'   => 'Neautentifikuotas vartotojas. Prisijunkite per HTML sąsają.',
    ], 401);
}

$pdo = getPDO();
$repository = new ApplicationRepository($pdo);
$service = new ApplicationService($repository);

$method = $_SERVER['REQUEST_METHOD'];

// GET /api/applications.php -> sąrašas (studentui – jo paties, adminui – visos)
if ($method === 'GET') {
    if ($user['role'] === 'student') {
        $apps = $repository->findAllForStudent((int)$user['id']);
    } else {
        $apps = $repository->findAll();
    }

    json_response($apps, 200);
}

// POST /api/applications.php -> sukurti ruošinį studentui
if ($method === 'POST') {
    if ($user['role'] !== 'student') {
        json_response([
            'success' => false,
            'error'   => 'Tik studentai gali kurti paraiškas per API.',
        ], 403);
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        json_response([
            'success' => false,
            'error'   => 'Neteisingas JSON formatas.',
        ], 400);
    }

    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $type = $data['type'] ?? '';

    $error = $service->createDraftForStudent(
        (int)$user['id'],
        (string)$title,
        (string)$description,
        (string)$type
    );

    if ($error !== null) {
        json_response([
            'success' => false,
            'error'   => $error,
        ], 400);
    }

    json_response([
        'success' => true,
        'message' => 'Paraiškos ruošinys sukurtas sėkmingai.',
    ], 201);
}

// Jei metodas nepalaikomas
json_response([
    'success' => false,
    'error'   => 'Nepalaikomas HTTP metodas.',
], 405);
