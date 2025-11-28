<?php
session_start();

require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/ApplicationRepository.php';
require __DIR__ . '/../../src/ApplicationService.php';

initDatabase();
initUsersTable();
initApplicationsTable();

$pdo = getPDO();
$repository = new ApplicationRepository($pdo);
$service = new ApplicationService($repository);

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$currentUser = findUserById((int)$_SESSION['user_id']);

if ($currentUser === null || $currentUser['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$application = $service->getSubmittedForRejection($id);
if (!$application) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = trim($_POST['rejection_comment'] ?? '');

    $error = $service->rejectWithComment($application['id'], $comment);

    if ($error === null) {
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Atmesti paraišką</title>
</head>
<body>
    <h1>Atmesti paraišką</h1>

    <p>
        Prisijungęs: <strong><?php echo htmlspecialchars($currentUser['name']); ?></strong>
        (<?php echo htmlspecialchars($currentUser['role']); ?>)
        | <a href="index.php">Atgal į sąrašą</a>
        | <a href="../logout.php">Atsijungti</a>
    </p>

    <h2>Paraiška</h2>
    <p><strong>ID:</strong> <?php echo htmlspecialchars($application['id']); ?></p>
    <p><strong>Pavadinimas:</strong> <?php echo htmlspecialchars($application['title']); ?></p>
    <p><strong>Tipas:</strong> <?php echo htmlspecialchars($application['type']); ?></p>

    <?php if ($error !== null): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post">
        <div>
            <label>
                Atmetimo komentaras:<br>
                <textarea name="rejection_comment" rows="4" cols="40" required></textarea>
            </label>
        </div>
        <div>
            <button type="submit">Atmesti paraišką</button>
        </div>
    </form>
</body>
</html>
