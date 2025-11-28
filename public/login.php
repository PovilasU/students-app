<?php
session_start();

require __DIR__ . '/../src/db.php';

initDatabase();
initUsersTable();
initApplicationsTable();

$users = getAllUsers();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId <= 0) {
        $error = 'Please select a user.';
    } else {
        $user = findUserById($userId);

        if ($user === null) {
            $error = 'User not found.';
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
    <title>Login - Paraiškų sistema</title>
</head>
<body>
    <h1>Paraiškų sistema - Login</h1>

    <?php if ($error !== null): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post">
        <div>
            <label>
                Vartotojas:
                <select name="user_id" required>
                    <option value="">-- Pasirinkite --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo (int)$user['id']; ?>">
                            <?php echo htmlspecialchars($user['name'] . ' (' . $user['role'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div>
            <button type="submit">Prisijungti</button>
        </div>
    </form>
</body>
</html>
