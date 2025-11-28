<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Atmesti paraišką</title>
    <link rel="stylesheet" href="/css/water.css">
</head>
<body>
<h1>Atmesti paraišką</h1>

<p>
    Prisijungęs:
    <strong><?php echo htmlspecialchars($currentUser['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
    (<?php echo htmlspecialchars($currentUser['role'] === 'student' ? 'studentas' : 'administratorius', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>)
    | <a href="/applications/index.php">Atgal į paraiškų sąrašą</a>
    | <a href="/logout.php">Atsijungti</a>
</p>

<h2>Paraiškos duomenys</h2>
<p><strong>ID:</strong> <?php echo htmlspecialchars($application['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
<p><strong>Pavadinimas:</strong> <?php echo htmlspecialchars($application['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
<p><strong>Tipas:</strong> <?php echo htmlspecialchars($application['type'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>

<?php if (!empty($error)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="/applications/reject.php?id=<?php echo (int)$application['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

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
