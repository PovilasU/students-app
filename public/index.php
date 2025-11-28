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

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
