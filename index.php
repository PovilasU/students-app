<?php

require __DIR__ . '/db.php';

initDatabase();
initApplicationsTable();

$pdo = getPDO();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? '');

    if ($title === '' || $description === '' || $type === '') {
        $error = 'Please fill all fields.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO applications (title, description, type, status, created_at)
            VALUES (:title, :description, :type, :status, :created_at)
        ");

        $stmt->execute([
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

$stmt = $pdo->prepare("INSERT INTO test (created_at) VALUES (:created_at)");
$stmt->execute([
    ':created_at' => date('Y-m-d H:i:s'),
]);
$testCount = (int)$pdo->query("SELECT COUNT(*) FROM test")->fetchColumn();

$applicationsStmt = $pdo->query("
    SELECT id, title, type, status, created_at
    FROM applications
    ORDER BY created_at DESC
");
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
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>
</html>
