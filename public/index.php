<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/Router.php';
require __DIR__ . '/../src/csrf.php';
require __DIR__ . '/../src/View.php';
require __DIR__ . '/../src/ApplicationRepository.php';
require __DIR__ . '/../src/ApplicationService.php';
require __DIR__ . '/../src/ApplicationController.php';

initDatabase();
initUsersTable();
initApplicationsTable();

$router = new Router();

/**
 * Helper: redirect
 */
function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

/**
 * Helper: gauti prisijungusį vartotoją arba null
 */
function current_user(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return findUserById((int)$_SESSION['user_id']);
}

/**
 * Helper: sukurti controller + service + repo
 */
function make_application_controller(): ApplicationController {
    $pdo = getPDO();
    $repository = new ApplicationRepository($pdo);
    $service = new ApplicationService($repository);
    return new ApplicationController($service);
}

/**
 * ROOT -> /login
 */
$router->get('/', function () {
    redirect('/login');
});

/**
 * TEST ROUTE (gali palikti arba ištrinti vėliau)
 */
$router->get('/router-test', function () {
    echo "<h1>Router works!</h1>";
    echo "<p>Route: <code>/router-test</code></p>";
});

/**
 * LOGIN (GET) – forma
 */
$router->get('/login', function () {
    $error = $_SESSION['login_error'] ?? null;
    unset($_SESSION['login_error']);

    include __DIR__ . '/../views/auth/login.php';
});

/**
 * LOGIN (POST) – logika
 */
$router->post('/login', function () {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? null;

    if (!csrf_verify($token)) {
        $_SESSION['login_error'] = 'Neteisingas saugumo žetonas. Perkraukite puslapį ir bandykite dar kartą.';
        redirect('/login');
    }

    if ($email === '' || $password === '') {
        $_SESSION['login_error'] = 'Prašome įvesti el. paštą ir slaptažodį.';
        redirect('/login');
    }

    $user = findUserByEmail($email);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['login_error'] = 'Neteisingas el. paštas arba slaptažodis.';
        redirect('/login');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];

    redirect('/applications');
});

/**
 * REGISTER (GET) – forma
 */
$router->get('/register', function () {
    $error = $_SESSION['register_error'] ?? null;
    $success = $_SESSION['register_success'] ?? null;
    unset($_SESSION['register_error'], $_SESSION['register_success']);

    include __DIR__ . '/../views/auth/register.php';
});

/**
 * REGISTER (POST) – logika
 */
$router->post('/register', function () {
    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_verify($token)) {
        $_SESSION['register_error'] = 'Neteisingas saugumo žetonas. Perkraukite puslapį ir bandykite dar kartą.';
        redirect('/register');
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password_confirm'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $password2 === '') {
        $_SESSION['register_error'] = 'Visi laukai yra privalomi.';
        redirect('/register');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = 'Neteisingas el. pašto formatas.';
        redirect('/register');
    }

    if ($password !== $password2) {
        $_SESSION['register_error'] = 'Slaptažodžiai nesutampa.';
        redirect('/register');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $created = createUser($name, $email, $hash, 'student');

    if (!$created) {
        $_SESSION['register_error'] = 'Toks el. paštas jau egzistuoja.';
        redirect('/register');
    }

    $_SESSION['register_success'] = 'Registracija sėkminga. Dabar galite prisijungti.';
    redirect('/register');
});

/**
 * LOGOUT
 */
$router->get('/logout', function () {
    session_unset();
    session_destroy();
    redirect('/login');
});

/**
 * APPLICATIONS – LIST + CREATE (GET)
 */
$router->get('/applications', function () {
    $currentUser = current_user();
    if ($currentUser === null) {
        redirect('/login');
    }

    $controller = make_application_controller();
    $view = new View();

    $flashError = $_SESSION['flash_error'] ?? null;
    unset($_SESSION['flash_error']);

    $error = null;

    $applications = $controller->list($currentUser);

    $view->render('applications/list.php', [
        'currentUser'  => $currentUser,
        'flashError'   => $flashError,
        'error'        => $error,
        'applications' => $applications,
    ]);
});

/**
 * APPLICATIONS – CREATE (POST)
 */
$router->post('/applications', function () {
    $currentUser = current_user();
    if ($currentUser === null) {
        redirect('/login');
    }

    $controller = make_application_controller();
    $view = new View();

    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_verify($token)) {
        $error = 'Neteisingas saugumo žetonas. Perkraukite puslapį ir bandykite dar kartą.';
    } else {
        $error = $controller->create($currentUser, $_POST);
    }

    if ($error === null) {
        redirect('/applications');
    }

    $flashError = $_SESSION['flash_error'] ?? null;
    unset($_SESSION['flash_error']);

    $applications = $controller->list($currentUser);

    $view->render('applications/list.php', [
        'currentUser'  => $currentUser,
        'flashError'   => $flashError,
        'error'        => $error,
        'applications' => $applications,
    ]);
});

/**
 * APPLICATIONS – SUBMIT (student)
 */
$router->get('/applications/submit', function () {
    $currentUser = current_user();
    if ($currentUser === null) {
        redirect('/login');
    }

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        redirect('/applications');
    }

    $controller = make_application_controller();

    $err = $controller->submit($currentUser, $id);
    if ($err !== null) {
        $_SESSION['flash_error'] = $err;
    }

    redirect('/applications');
});

/**
 * APPLICATIONS – APPROVE (admin)
 */
$router->get('/applications/approve1', function () {
    $currentUser = current_user();
    if ($currentUser === null) {
        redirect('/login');
    }

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        redirect('/applications');
    }

    if ($currentUser['role'] !== 'admin') {
        redirect('/applications');
    }

    $controller = make_application_controller();
    $controller->approve($currentUser, $id);

    redirect('/applications');
});

$router->get('/applications/approve', function () {
    echo "<h1>DEBUG: /applications/approve route HIT</h1>";
    echo "<p>GET id = " . htmlspecialchars($_GET['id'] ?? 'n/a', ENT_QUOTES, 'UTF-8') . "</p>";
    exit;
});

/**
 * TODO vėliau:
 *  - /applications/edit
 *  - /applications/reject
 *  - perkelti edit/reject view'us į routerį
 */

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
