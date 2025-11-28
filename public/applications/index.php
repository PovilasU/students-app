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

if ($currentUser === null) {
    unset($_SESSION['user_id']);
    header('Location: ../login.php');
    exit;
}

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$error = null;

// submit
if (
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'submit'
) {
    $id = (int)$_GET['id'];

    $err = $controller->submit($currentUser, $id);
    if ($err !== null) {
        $_SESSION['flash_error'] = $err;
    }

    header('Location: index.php');
    exit;
}

// approve
if (
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'approve'
) {
    $id = (int)$_GET['id'];

    $controller->approve($currentUser, $id);

    header('Location: index.php');
    exit;
}

// create
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = $controller->create($currentUser, $_POST);

    if ($error === null) {
        header('Location: index.php');
        exit;
    }
}

$applications = $controller->list($currentUser);
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
        | <a href="index.php">Paraiškos</a>
        | <a href="../logout.php">Atsijungti</a>
    </p>

    <?php if ($flashError !== null): ?>
        <p style="color: red;"><?php echo htmlspecialchars($flashError); ?></p>
    <?php endif; ?>

    <?php if ($currentUser['role'] === 'student'): ?>
        <h2>Nauja paraiška</h2>

        <?php if ($error !== null): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post" action="index.php">
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
                            <a href="edit.php?id=<?php echo (int)$app['id']; ?>">Redaguoti</a>
                            |
                            <a href="index.php?action=submit&id=<?php echo (int)$app['id']; ?>">Pateikti</a>
                        <?php elseif ($currentUser['role'] === 'admin' && $app['status'] === 'submitted'): ?>
                            <a href="index.php?action=approve&id=<?php echo (int)$app['id']; ?>">Patvirtinti</a>
                            |
                            <a href="reject.php?id=<?php echo (int)$app['id']; ?>">Atmesti</a>
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
