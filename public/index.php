<?php
session_start();

require __DIR__ . '/../src/db.php';

initDatabase();
initUsersTable();
initApplicationsTable();

$pdo = getPDO();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUser = findUserById((int)$_SESSION['user_id']);

if ($currentUser === null) {
    unset($_SESSION['user_id']);
    header('Location: login.php');
    exit;
}

// student submit
if (
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'submit' &&
    $currentUser['role'] === 'student'
) {
    $id = (int)$_GET['id'];

    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE applications
            SET status = 'submitted'
            WHERE id = :id
              AND status = 'draft'
              AND student_id = :student_id
        ");
        $stmt->execute([
            ':id' => $id,
            ':student_id' => $currentUser['id'],
        ]);
    }

    header('Location: index.php');
    exit;
}

// admin approve/reject
if (
    isset($_GET['action'], $_GET['id']) &&
    $currentUser['role'] === 'admin'
) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($id > 0 && in_array($action, ['approve', 'reject'], true)) {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';

        $stmt = $pdo->prepare("
            UPDATE applications
            SET status = :status
            WHERE id = :id
              AND status = 'submitted'
        ");
        $stmt->execute([
            ':status' => $newStatus,
            ':id' => $id,
        ]);
    }

    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($currentUser['role'] !== 'student') {
        $error = 'Only students can create applications.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = trim($_POST['type'] ?? '');

        if ($title === '' || $description === '' || $type === '') {
            $error = 'Please fill all fields.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO applications (student_id, title, description, type, status, created_at)
                VALUES (:student_id, :title, :description, :type, :status, :created_at)
            ");

            $stmt->execute([
                ':student_id' => $currentUser['id'],
                ':title' => $title,
                ':description' => $description,
                ':type' => $type,
                ':status' => 'draft',
                ':created_at' => date('Y-m-d H:i:s'),
            ]);

            header('Location: index.php');
            exit;
        }
    }
}

$stmt = $pdo->prepare("INSERT INTO test (created_at) VALUES (:created_at)");
$stmt->execute([
    ':created_at' => date('Y-m-d H:i:s'),
]);
$testCount = (int)$pdo->query("SELECT COUNT(*) FROM test")->fetchColumn();

if ($currentUser['role'] === 'student') {
    $applicationsStmt = $pdo->prepare("
        SELECT id, title, type, status, created_at
        FROM applications
        WHERE student_id = :student_id
        ORDER BY created_at DESC
    ");
    $applicationsStmt->execute([':student_id' => $currentUser['id']]);
} else {
    $applicationsStmt = $pdo->query("
        SELECT id, title, type, status, created_at
        FROM applications
        ORDER BY created_at DESC
    ");
}
$applications = $applicationsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Paraiškų sistema</title>
</head>
<body>
    <h1>Paraiškų sistema</h1>

    <p>
        Prisijungęs: <strong><?php echo htmlspecialchars($currentUser['name']); ?></strong>
        (<?php echo htmlspecialchars($currentUser['role']); ?>)
        | <a href="logout.php">Atsijungti</a>
    </p>

    <?php if ($currentUser['role'] === 'student'): ?>
        <h2>Nauja paraiška</h2>

        <?php if ($error !== null): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post">
            <div>
                <label>
                    Pavadinimas:
                    <input type="text" name="title" required>
                </label>
            </div>
            <div>
                <label>
                    Tipas:
                    <input type="text" name="type" required>
                </label>
            </div>
            <div>
                <label>
                    Aprašymas:<br>
                    <textarea name="description" rows="4" cols="40" required></textarea>
                </label>
            </div>
            <div>
                <button type="submit">Sukurti paraišką (draft)</button>
            </div>
        </form>

        <hr>
    <?php endif; ?>

    <h2>DB statusas</h2>
    <p>Test lentelėje įrašų: <strong><?php echo $testCount; ?></strong></p>

    <hr>

    <h2>Paraiškų sąrašas</h2>

    <?php if (empty($applications)): ?>
        <p>Paraiškų dar nėra.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pavadinimas</th>
                    <th>Tipas</th>
                    <th>Statusas</th>
                    <th>Sukurta</th>
                    <th>Veiksmai</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?php echo htmlspecialchars($app['id']); ?></td>
                    <td><?php echo htmlspecialchars($app['title']); ?></td>
                    <td><?php echo htmlspecialchars($app['type']); ?></td>
                    <td><?php echo htmlspecialchars($app['status']); ?></td>
                    <td><?php echo htmlspecialchars($app['created_at']); ?></td>
                    <td>
                        <?php if ($currentUser['role'] === 'student' && $app['status'] === 'draft'): ?>
                            <a href="index.php?action=submit&id=<?php echo (int)$app['id']; ?>">
                                Pateikti
                            </a>
                        <?php elseif ($currentUser['role'] === 'admin' && $app['status'] === 'submitted'): ?>
                            <a href="index.php?action=approve&id=<?php echo (int)$app['id']; ?>">Patvirtinti</a>
                            |
                            <a href="index.php?action=reject&id=<?php echo (int)$app['id']; ?>">Atmesti</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>
</html>
