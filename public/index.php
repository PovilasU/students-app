<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/Router.php';

// Kol kas – tik DB init, vėliau prikabinsim controllerius ir view
initDatabase();
initUsersTable();
initApplicationsTable();

$router = new Router();

// Homepage: kol kas tiesiog redirect į /login
$router->get('/', function () {
    header('Location: /login.php');
    exit;
});

// Testinis route, kad matytum, jog routeris veikia
$router->get('/router-test', function () {
    echo "<h1>Veikia!</h1>";
    echo "<p>Jūs sėkmingai pasiekėte route <code>/router-test</code> per Router sluoksnį.</p>";
});

// LOGIN (GET) - rodo prisijungimo formą
$router->get('/login', function () {
    require __DIR__ . '/../src/View.php';
    require __DIR__ . '/../src/csrf.php';
    require __DIR__ . '/../src/db.php';

    $error = $_SESSION['login_error'] ?? null;
    unset($_SESSION['login_error']);

    include __DIR__ . '/../views/auth/login.php';
});

// LOGIN (POST) - apdoroja formą
$router->post('/login', function () {
    require __DIR__ . '/../src/db.php';
    require __DIR__ . '/../src/csrf.php';

    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_verify($token)) {
        $_SESSION['login_error'] = 'Neteisingas saugumo žetonas.';
        header('Location: /login');
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = findUserByEmail($email);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['login_error'] = 'Neteisingas el. paštas arba slaptažodis.';
        header('Location: /login');
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];

    header('Location: /applications');
    exit;
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
