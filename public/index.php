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

// REGISTER (GET) - rodo registracijos formą
$router->get('/register', function () {
    require __DIR__ . '/../src/csrf.php';

    $error = $_SESSION['register_error'] ?? null;
    $success = $_SESSION['register_success'] ?? null;
    unset($_SESSION['register_error'], $_SESSION['register_success']);

    include __DIR__ . '/../views/auth/register.php';
});

// REGISTER (POST) - apdoroja registracijos formą
$router->post('/register', function () {
    require __DIR__ . '/../src/db.php';
    require __DIR__ . '/../src/csrf.php';

    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_verify($token)) {
        $_SESSION['register_error'] = 'Neteisingas saugumo žetonas. Perkraukite puslapį ir bandykite dar kartą.';
        header('Location: /register');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password_confirm'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $password2 === '') {
        $_SESSION['register_error'] = 'Visi laukai yra privalomi.';
        header('Location: /register');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = 'Neteisingas el. pašto formatas.';
        header('Location: /register');
        exit;
    }

    if ($password !== $password2) {
        $_SESSION['register_error'] = 'Slaptažodžiai nesutampa.';
        header('Location: /register');
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $created = createUser($name, $email, $hash, 'student');

    if (!$created) {
        $_SESSION['register_error'] = 'Toks el. paštas jau egzistuoja.';
        header('Location: /register');
        exit;
    }

    $_SESSION['register_success'] = 'Registracija sėkminga. Dabar galite prisijungti.';
    header('Location: /register');
    exit;
});


$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
