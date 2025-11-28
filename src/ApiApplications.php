<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ApplicationRepository.php';
require_once __DIR__ . '/ApplicationService.php';

function api_current_user_from_session(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    return findUserById((int)$_SESSION['user_id']);
}

/**
 * Pagrindinė API handler'io funkcija.
 *
 * @return array [int $statusCode, array $body]
 */
function api_applications_handle(string $method, array $query, ?string $rawBody): array
{
    initDatabase();
    initUsersTable();
    initApplicationsTable();

    $user = api_current_user_from_session();
    if ($user === null) {
        return [
            401,
            ['success' => false, 'error' => 'Neautentifikuotas vartotojas. Prisijunkite per /api/login arba HTML.'],
        ];
    }

    $pdo = getPDO();
    $repository = new ApplicationRepository($pdo);
    $service = new ApplicationService($repository);

    // GET: sąrašas arba vienas įrašas
    if ($method === 'GET') {
        $id = isset($query['id']) ? (int)$query['id'] : 0;

        if ($id > 0) {
            $app = $repository->findById($id);
            if (!$app) {
                return [404, ['success' => false, 'error' => 'Paraiška nerasta.']];
            }

            if ($user['role'] === 'student' && (int)$app['student_id'] !== (int)$user['id']) {
                return [403, ['success' => false, 'error' => 'Neturite teisės peržiūrėti šios paraiškos.']];
            }

            return [200, $app];
        }

        if ($user['role'] === 'student') {
            $apps = $repository->findAllForStudent((int)$user['id']);
        } else {
            $apps = $repository->findAll();
        }

        return [200, $apps];
    }

    // POST: sukurti ruošinį (tik studentas)
    if ($method === 'POST') {
        if ($user['role'] !== 'student') {
            return [403, ['success' => false, 'error' => 'Tik studentai gali kurti paraiškas.']];
        }

        $data = json_decode($rawBody ?? '', true);
        if (!is_array($data)) {
            return [400, ['success' => false, 'error' => 'Neteisingas JSON formatas.']];
        }

        $title = (string)($data['title'] ?? '');
        $description = (string)($data['description'] ?? '');
        $type = (string)($data['type'] ?? '');

        $error = $service->createDraftForStudent(
            (int)$user['id'],
            $title,
            $description,
            $type
        );

        if ($error !== null) {
            return [400, ['success' => false, 'error' => $error]];
        }

        return [201, ['success' => true, 'message' => 'Paraiškos ruošinys sukurtas sėkmingai.']];
    }

    // PATCH: submit / approve / reject
    if ($method === 'PATCH') {
        $id = isset($query['id']) ? (int)$query['id'] : 0;
        if ($id <= 0) {
            return [400, ['success' => false, 'error' => 'Trūksta paraiškos ID.']];
        }

        $data = json_decode($rawBody ?? '', true);
        if (!is_array($data)) {
            return [400, ['success' => false, 'error' => 'Neteisingas JSON formatas.']];
        }

        $action = $data['action'] ?? '';

        if ($action === 'submit') {
            if ($user['role'] !== 'student') {
                return [403, ['success' => false, 'error' => 'Tik studentai gali pateikti paraiškas.']];
            }

            $err = $service->submitDraftForStudent($id, (int)$user['id']);
            if ($err !== null) {
                return [400, ['success' => false, 'error' => $err]];
            }

            return [200, ['success' => true, 'message' => 'Paraiška sėkmingai pateikta.']];
        }

        if ($action === 'approve') {
            if ($user['role'] !== 'admin') {
                return [403, ['success' => false, 'error' => 'Tik administratorius gali patvirtinti paraiškas.']];
            }

            $service->approveSubmittedByAdmin($id);
            return [200, ['success' => true, 'message' => 'Paraiška patvirtinta.']];
        }

        if ($action === 'reject') {
            if ($user['role'] !== 'admin') {
                return [403, ['success' => false, 'error' => 'Tik administratorius gali atmesti paraiškas.']];
            }

            $comment = (string)($data['comment'] ?? '');
            $err = $service->rejectWithComment($id, $comment);
            if ($err !== null) {
                return [400, ['success' => false, 'error' => $err]];
            }

            return [
                200,
                [
                    'success' => true,
                    'message' => 'Paraiška atmesta.',
                    'comment' => $comment,
                ],
            ];
        }

        return [400, ['success' => false, 'error' => 'Nežinomas veiksmas.']];
    }

    return [405, ['success' => false, 'error' => 'Nepalaikomas HTTP metodas.']];
}
