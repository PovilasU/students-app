<?php

require_once __DIR__ . '/db.php';

function api_login_handle(string $method, ?string $rawBody): array
{
    if ($method !== 'POST') {
        return [
            405,
            ['success' => false, 'error' => 'Leidžiamas tik POST metodas.'],
        ];
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    initDatabase();
    initUsersTable();
    initApplicationsTable();

    $data = json_decode($rawBody ?? '', true);
    if (!is_array($data)) {
        return [
            400,
            ['success' => false, 'error' => 'Neteisingas JSON formatas.'],
        ];
    }

    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if ($email === '' || $password === '') {
        return [
            400,
            ['success' => false, 'error' => 'El. paštas ir slaptažodis privalomi.'],
        ];
    }

    $user = findUserByEmail($email);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return [
            401,
            ['success' => false, 'error' => 'Neteisingas el. paštas arba slaptažodis.'],
        ];
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];

    return [
        200,
        [
            'success' => true,
            'user' => [
                'id'   => (int)$user['id'],
                'name' => $user['name'],
                'role' => $user['role'],
            ],
        ],
    ];
}
