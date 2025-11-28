<?php
session_start();

require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/ApplicationRepository.php';
require __DIR__ . '/../../src/ApplicationService.php';
require __DIR__ . '/../../src/ApplicationController.php';

initDatabase();
initUsersTable();
initApplicationsTable();

$pdo = getPDO();
$repository = new ApplicationRepository($pdo);
$service = new ApplicationService($repository);
$controller = new ApplicationController($service);

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
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

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = $controller->updateEdit($currentUser, $id, $_POST);

    if ($error === null) {
        header('Location: index.php');
        exit;
    }

    $application = [
        'id' => $id,
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'type' => trim($_POST['type'] ?? ''),
    ];
} else {
    $application = $controller->getEditData($currentUser, $id);
    if (!$application) {
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
        | <a href="../logout.php">Atsijungti</a>
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
