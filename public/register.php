<?php
session_start();

require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/csrf.php';

initDatabase();
initUsersTable();
initApplicationsTable();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_verify($token)) {
        $error = 'Neteisingas saugumo žetonas. Perkraukite puslapį ir bandykite dar kartą.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password_confirm'] ?? '';

        if ($name === '' || $email === '' || $password === '' || $password2 === '') {
            $error = 'Visi laukai yra privalomi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Neteisingas el. pašto formatas.';
        } elseif ($password !== $password2) {
            $error = 'Slaptažodžiai nesutampa.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $created = createUser($name, $email, $hash, 'student');
            if (!$created) {
                $error = 'Toks el. paštas jau naudojamas.';
            } else {
                $success = 'Registracija sėkminga. Dabar galite prisijungti.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Registracija</title>
    <link rel="stylesheet" href="/css/water.css">
</head>
<body>
    <h1>Paraiškų sistema – registracija</h1>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" action="/register.php">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div>
            <label>
                Vardas:
                <input type="text" name="name" required>
            </label>
        </div>

        <div>
            <label>
                El. paštas:
                <input type="email" name="email" required>
            </label>
        </div>

        <div>
            <label>
                Slaptažodis:
                <input type="password" name="password" required>
            </label>
        </div>

        <div>
            <label>
                Pakartokite slaptažodį:
                <input type="password" name="password_confirm" required>
            </label>
        </div>

        <div>
            <button type="submit">Registruotis</button>
        </div>
    </form>

    <p>Jau turite paskyrą? <a href="/login.php">Prisijunkite</a></p>
</body>
</html>
