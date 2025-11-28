<?php
require __DIR__ . '/db.php';

// Inicializuojam DB lenteles
initDatabase();           // mūsų test lentelė
initApplicationsTable();  // nauja paraiškų lentelė

$pdo = getPDO();

// DB testui – vis dar paliekam (skaičius kils kas reload)
$stmt = $pdo->prepare("INSERT INTO test (created_at) VALUES (:created_at)");
$stmt->execute([
    ':created_at' => date('Y-m-d H:i:s'),
]);

$testCount = (int)$pdo->query("SELECT COUNT(*) FROM test")->fetchColumn();

// Pasiimam visas paraiškas (kol kas lentelė bus tuščia)
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
    <title>Paraiškų sistema - Hello</title>
</head>
<body>
    <h1>Paraiškų sistema</h1>
    <p>Šita bus mūsų paraiškų sistemos pradžia.</p>

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
