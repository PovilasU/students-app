<?php
session_start();

require __DIR__ . '/../src/db.php';

initDatabase();
initUsersTable();
initApplicationsTable();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Įveskite el. paštą ir slaptažodį.';
    } else {
        $pdo = getPDO();

        $stmt = $pdo->prepare("
            SELECT id, name, email, password_hash, role
            FROM users
            WHERE email = :email
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Neteisingas el. paštas arba slaptažodis.';
        } else {
            $_SESSION['user_id'] = $user['id'];
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
    <title>Prisijungimas - Paraiškų sistema</title>
    <link rel="stylesheet" href="css/water.css">
</head>
<body>
    <h1>Paraiškų sistema - prisijungimas</h1>

    <?php if ($error !== null): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post">
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
    
    <p>
        Neturite paskyros?
        <a href="register.php">Registruokitės</a>
    </p>


    <hr>

    <h2>Demo prisijungimai</h2>
    <ul>
        <li>Studentas: <code>student@example.com</code> / <code>student123</code></li>
        <li>Administratorius: <code>admin@example.com</code> / <code>admin123</code></li>
    </ul>
</body>
</html>
