<?php
session_start();

require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/csrf.php';

initDatabase();
initUsersTable();
initApplicationsTable();

$error = null;

// Rate limiting
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_last_attempt'] = time();
}

if (
    $_SESSION['login_attempts'] >= 5
    && (time() - $_SESSION['login_last_attempt'] < 300)
) {
    $error = 'Per daug nesėkmingų bandymų. Bandykite dar kartą po kelių minučių.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) {
    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_verify($token)) {
        $error = 'Neteisingas saugumo žetonas. Perkraukite puslapį ir bandykite dar kartą.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Prašome įvesti el. paštą ir slaptažodį.';
        } else {
            $user = findUserByEmail($email);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = 'Neteisingas el. paštas arba slaptažodis.';
                $_SESSION['login_attempts']++;
                $_SESSION['login_last_attempt'] = time();
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['login_attempts'] = 0;

                header('Location: /applications/index.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Prisijungimas</title>
    <link rel="stylesheet" href="/css/water.css">
</head>
<body>
    <h1>Paraiškų sistema – prisijungimas</h1>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" action="/login.php">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

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
            <button type="submit">Prisijungti</button>
        </div>
    </form>

    
    <hr>

    <h2>Demo prisijungimai</h2>
    <ul>
        <li>Studentas: <code>student@example.com</code> / <code>student123</code></li>
        <li>Administratorius: <code>admin@example.com</code> / <code>admin123</code></li>
    </ul>

    <p>Neturite paskyros? <a href="/register.php">Registruokitės</a></p>
</body>
</html>
