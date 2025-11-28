<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Paraiškų sistema</title>
    <link rel="stylesheet" href="/css/water.css">
</head>
<body>
<?php
function lt_status_label(string $status): string {
    return match ($status) {
        'draft' => 'Ruošinys',
        'submitted' => 'Pateikta',
        'approved' => 'Patvirtinta',
        'rejected' => 'Atmesta',
        default => $status,
    };
}
?>
<h1>Paraiškų sistema</h1>

<p>
    Prisijungęs:
    <strong><?php echo htmlspecialchars($currentUser['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
    (<?php echo htmlspecialchars($currentUser['role'] === 'student' ? 'studentas' : 'administratorius', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>)
    | <a href="/applications/index.php">Paraiškos</a>
    | <a href="/logout.php">Atsijungti</a>
</p>

<?php if (!empty($flashError)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($flashError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if ($currentUser['role'] === 'student'): ?>
    <h2>Nauja paraiška</h2>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" action="/applications/index.php">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

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
            <button type="submit">Sukurti paraišką (ruošinys)</button>
        </div>
    </form>

    <hr>
<?php endif; ?>

<h2>Paraiškų sąrašas</h2>

<?php if (empty($applications)): ?>
    <p>Paraiškų dar nėra.</p>
<?php else: ?>
    <table>
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
                <td><?php echo htmlspecialchars($app['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($app['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($app['type'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(lt_status_label($app['status']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($app['created_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                <td>
                    <?php
                    if ($app['status'] === 'rejected' && $app['rejection_comment'] !== null) {
                        echo htmlspecialchars($app['rejection_comment'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    }
                    ?>
                </td>
                <td>
                    <?php if ($currentUser['role'] === 'student' && $app['status'] === 'draft'): ?>
                        <a href="/applications/edit.php?id=<?php echo (int)$app['id']; ?>">Redaguoti ruošinį</a>
                        |
                        <a href="/applications/index.php?action=submit&id=<?php echo (int)$app['id']; ?>">Pateikti paraišką</a>
                    <?php elseif ($currentUser['role'] === 'admin' && $app['status'] === 'submitted'): ?>
                        <a href="/applications/index.php?action=approve&id=<?php echo (int)$app['id']; ?>">Patvirtinti</a>
                        |
                        <a href="/applications/reject.php?id=<?php echo (int)$app['id']; ?>">Atmesti</a>
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
