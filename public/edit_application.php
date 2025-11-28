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

if ($currentUser === null || $currentUser['role'] !== 'student') {
    header('Location: index.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, title, description, type, status
    FROM applications
    WHERE id = :id
      AND student_id = :student_id
");
$stmt->execute([
    ':id' => $id,
    ':student_id' => $currentUser['id'],
]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application || $application['status'] !== 'draft') {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? '');

    if ($title === '' || $description === '' || $type === '') {
        $error = 'Please fill all fields.';
    } else {
        $updateStmt = $pdo->prepare("
            UPDATE applications
            SET title = :title,
                description = :description,
                type = :type
            WHERE id = :id
              AND student_id = :student_id
              AND status = 'draft'
        ");

        $updateStmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':type' => $type,
            ':id' => $application['id'],
            ':student_id' => $currentUser['id'],
        ]);

        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Redaguoti paraišką</title>
</head>
<body>
    <h1>Redaguoti paraišką (draft)</h1>

    <p>
        Prisijungęs: <strong><?php echo htmlspecialchars($currentUser['name']); ?></strong>
        (<?php echo htmlspecialchars($currentUser['role']); ?>)
        | <a href="index.php">Atgal į sąrašą</a>
        | <a href="logout.php">Atsijungti</a>
    </p>

    <?php if ($error !== null): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post">
        <div>
            <label>
                Pavadinimas:
                <input type="text" name="title"
                       value="<?php echo htmlspecialchars($application['title']); ?>" required>
            </label>
        </div>
        <div>
            <label>
                Tipas:
                <input type="text" name="type"
                       value="<?php echo htmlspecialchars($application['type']); ?>" required>
            </label>
        </div>
        <div>
            <label>
                Aprašymas:<br>
                <textarea name="description" rows="4" cols="40" required><?php
                    echo htmlspecialchars($application['description']);
                ?></textarea>
            </label>
        </div>
        <div>
            <button type="submit">Išsaugoti</button>
        </div>
    </form>
</body>
</html>
