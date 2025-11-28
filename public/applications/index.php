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

if ($currentUser === null) {
    unset($_SESSION['user_id']);
    header('Location: /login.php');
    exit;
}

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$error = null;

// submit (student)
if (
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'submit'
) {
    $id = (int)$_GET['id'];

    $err = $controller->submit($currentUser, $id);
    if ($err !== null) {
        $_SESSION['flash_error'] = $err;
    }

    header('Location: /applications/index.php');
    exit;
}

// approve (admin)
if (
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'approve'
) {
    $id = (int)$_GET['id'];

    $controller->approve($currentUser, $id);

    header('Location: /applications/index.php');
    exit;
}

// create new (student)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = $controller->create($currentUser, $_POST);

    if ($error === null) {
        header('Location: /applications/index.php');
        exit;
    }
}

$applications = $controller->list($currentUser);

// DEBUG: kad matytum, jog tikrai naudojamas view
// gali ištrinti, kai įsitikinsi
// echo "[DEBUG] Naudojamas views/applications/list.php<br>";

$view->render('applications/list.php', [
    'currentUser' => $currentUser,
    'flashError'  => $flashError,
    'error'       => $error,
    'applications'=> $applications,
]);
