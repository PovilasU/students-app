<?php
session_start();

require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/ApplicationRepository.php';
require __DIR__ . '/../../src/ApplicationService.php';
require __DIR__ . '/../../src/ApplicationController.php';
require __DIR__ . '/../../src/View.php';

initDatabase();
initUsersTable();
initApplicationsTable();

$pdo = getPDO();
$repository = new ApplicationRepository($pdo);
$service = new ApplicationService($repository);
$controller = new ApplicationController($service);
$view = new View();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$currentUser = findUserById((int)$_SESSION['user_id']);

if ($currentUser === null || $currentUser['role'] !== 'student') {
    header('Location: /applications/index.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /applications/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = $controller->updateEdit($currentUser, $id, $_POST);

    if ($error === null) {
        header('Location: /applications/index.php');
        exit;
    }

    $application = [
        'id' => $id,
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'type' => trim($_POST['type'] ?? ''),
    ];
} else {
    $application = $controller->getEditData($currentUser, $id);
    if (!$application) {
        header('Location: /applications/index.php');
        exit;
    }
}

$view->render('applications/edit.php', [
    'currentUser' => $currentUser,
    'application' => $application,
    'error' => $error,
]);
