<?php
require __DIR__ . '/db.php';

initDatabase();

$pdo = getPDO();

$stmt = $pdo->prepare("INSERT INTO test (created_at) VALUES (:created_at)");
$stmt->execute([
    ':created_at' => date('Y-m-d H:i:s'),
]);

$count = (int)$pdo->query("SELECT COUNT(*) FROM test")->fetchColumn();

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hello world + DB</title>
</head>
<body>
    <h1>Hello world</h1>
    <p>Šita bus mūsų paraiškų sistemos pradžia.</p>

    <h2>DB statusas</h2>
    <p>Test lentelėje įrašų: <strong><?php echo $count; ?></strong></p>
    <p>(Kiekvieną kartą perkrovus puslapį, turėtų didėti skaičius.)</p>
</body>
</html>
