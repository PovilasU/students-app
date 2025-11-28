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

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

// student submit with max 3 submitted per type
if (
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'submit' &&
    $currentUser['role'] === 'student'
) {
    $id = (int)$_GET['id'];

    if ($id > 0) {
        $stmt = $pdo->prepare("
            SELECT id, type
            FROM applications
            WHERE id = :id
              AND student_id = :student_id
              AND status = 'draft'
        ");
        $stmt->execute([
            ':id' => $id,
            ':student_id' => $currentUser['id'],
        ]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($app) {
            $countStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM applications
                WHERE student_id = :student_id
                  AND type = :type
                  AND status = 'submitted'
            ");
            $countStmt->execute([
                ':student_id' => $currentUser['id'],
                ':type' => $app['type'],
            ]);
            $submittedCount = (int)$countStmt->fetchColumn();

            if ($submittedCount >= 3) {
                $_SESSION['flash_error'] = 'Jau turite 3 pateiktas šio tipo paraiškas.';
            } else {
                $updateStmt = $pdo->prepare("
                    UPDATE applications
                    SET status = 'submitted'
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    ':id' => $id,
                ]);
            }
        }
    }

    header('Location: index.php');
    exit;
}

// admin approve only
if (
    isset($_GET['action'], $_GET['id']) &&
    $currentUser['role'] === 'admin' &&
    $_GET['action'] === 'approve'
) {
    $id = (int)$_GET['id'];

    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE applications
            SET status = 'approved'
            WHERE id = :id
              AND status = 'submitted'
        ");
        $stmt->execute([
            ':id' => $id,
        ]);
    }

    header('Location: index.php');
    exit;
}

$error = null;

// create new application (student only)
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

// load applications list
if ($currentUser['role'] === 'student') {
    $applicationsStmt = $pdo->prepare("
        SELECT id, title, type, status, created_at, rejection_comment
        FROM applications
        WHERE student_id = :student_id
        ORDER BY created_at DESC
    ");
    $applicationsStmt->execute([':student_id' => $currentUser['id']]);
} else {
    $applicationsStmt = $pdo->query("
        SELECT id, title, type, status, created_at, rejection_comment
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

    <?php if ($flashError !== null): ?>
        <p style="color: red;"><?php echo htmlspecialchars($flashError); ?></p>
    <?php endif; ?>

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
                    <th>Atmetimo komentaras</th>
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
                        <?php
                        if ($app['status'] === 'rejected' && $app['rejection_comment'] !== null) {
                            echo htmlspecialchars($app['rejection_comment']);
                        }
                        ?>
                    </td>
                    <td>
                        <?php if ($currentUser['role'] === 'student' && $app['status'] === 'draft'): ?>
                            <a href="edit_application.php?id=<?php echo (int)$app['id']; ?>">Redaguoti</a>
                            |
                            <a href="index.php?action=submit&id=<?php echo (int)$app['id']; ?>">Pateikti</a>
                        <?php elseif ($currentUser['role'] === 'admin' && $app['status'] === 'submitted'): ?>
                            <a href="index.php?action=approve&id=<?php echo (int)$app['id']; ?>">Patvirtinti</a>
                            |
                            <a href="reject_application.php?id=<?php echo (int)$app['id']; ?>">Atmesti</a>
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
