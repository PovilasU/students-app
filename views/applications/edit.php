<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Redaguoti paraišką</title>
    <link rel="stylesheet" href="/css/water.css">
</head>
<body>
<h1>Redaguoti paraišką (ruošinys)</h1>

<p>
    Prisijungęs:
    <strong><?php echo htmlspecialchars($currentUser['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
    (<?php echo htmlspecialchars($currentUser['role'] === 'student' ? 'studentas' : 'administratorius', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>)
    | <a href="/applications/index.php">Atgal į paraiškų sąrašą</a>
    | <a href="/logout.php">Atsijungti</a>
</p>

<?php if (!empty($error)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="/applications/edit.php?id=<?php echo (int)$application['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

    <div>
        <label>
            Pavadinimas:
            <input type="text" name="title"
                   value="<?php echo htmlspecialchars($application['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" required>
        </label>
    </div>
    <div>
        <label>
            Tipas:
            <input type="text" name="type"
                   value="<?php echo htmlspecialchars($application['type'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" required>
        </label>
    </div>
    <div>
        <label>
            Aprašymas:<br>
            <textarea name="description" rows="4" cols="40" required><?php
                echo htmlspecialchars($application['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            ?></textarea>
        </label>
    </div>
    <div>
        <button type="submit">Išsaugoti ruošinį</button>
    </div>
</form>
</body>
</html>
