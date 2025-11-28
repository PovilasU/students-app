<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Paraiškų sistema</title>
    <link rel="stylesheet" href="/css/water.css">
</head>
<body>
    <h1>Paraiškų sistema</h1>

    <p>
        Prisijungęs: <strong><?php echo htmlspecialchars($currentUser['name']); ?></strong>
        (<?php echo htmlspecialchars($currentUser['role']); ?>)
        | <a href="/applications/index.php">Paraiškos</a>
        | <a href="/logout.php">Atsijungti</a>
    </p>

    <?php if (!empty($flashError)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($flashError); ?></p>
    <?php endif; ?>

    <?php if ($currentUser['role'] === 'student'): ?>
        <h2>Nauja paraiška</h2>

        <?php if (!empty($error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post" action="/applications/index.php">
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
                            <a href="/applications/edit.php?id=<?php echo (int)$app['id']; ?>">Redaguoti</a>
                            |
                            <a href="/applications/index.php?action=submit&id=<?php echo (int)$app['id']; ?>">Pateikti</a>
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
