<?php
session_start();

require __DIR__ . '/../src/db.php';

initDatabase();
initUsersTable();
initApplicationsTable();

// if already logged in, go to applications
if (isset($_SESSION['user_id'])) {
    header('Location: applications/index.php');
    exit;
}

$error = null;
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $passwordConfirm === '') {
        $error = 'Užpildykite visus laukus.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Neteisingas el. pašto formatas.';
    } elseif (strlen($password) < 6) {
        $error = 'Slaptažodis turi būti bent 6 simbolių.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Slaptažodžiai nesutampa.';
    } else {
        $pdo = getPDO();

        // check if email already exists
        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = :email
        ");
        $stmt->execute([':email' => $email]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $error = 'Naudotojas su tokiu el. paštu jau egzistuoja.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $pdo->prepare("
                INSERT INTO users (name, email, password_hash, role)
                VALUES (:name, :email, :password_hash, :role)
            ");
            $insert->execute([
                ':name' => $name,
                ':email' => $email,
                ':password_hash' => $passwordHash,
                ':role' => 'student',
            ]);

            $userId = (int)$pdo->lastInsertId();
            $_SESSION['user_id'] = $userId;

            header('Location: applications/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Registracija - Paraiškų sistema</title>
    <link rel="stylesheet" href="css/water.css">
</head>
<body>
    <h1>Paraiškų sistema - registracija</h1>

    <p>
        Jau turite paskyrą?
        <a href="login.php">Prisijunkite</a>
    </p>

    <?php if ($error !== null): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post" action="register.php">
        <div>
            <label>
                Vardas:
                <input type="text" name="name"
                       value="<?php echo htmlspecialchars($name); ?>" required>
            </label>
        </div>
        <div>
            <label>
                El. paštas:
                <input type="email" name="email"
                       value="<?php echo htmlspecialchars($email); ?>" required>
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
</body>
</html>
