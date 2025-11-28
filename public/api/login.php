<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../src/ApiLogin.php';

[$status, $body] = api_login_handle(
    $_SERVER['REQUEST_METHOD'],
    file_get_contents('php://input') ?: null
);

http_response_code($status);
echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
