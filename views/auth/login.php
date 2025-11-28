<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Prisijungimas</title>
    <link rel="stylesheet" href="/css/water.css">
</head>
<body>

<h1>Paraiškų sistema – prisijungimas</h1>

<?php if (!empty($error)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="/login">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

    <label>
        El. paštas:
        <input type="email" name="email" required>
    </label>

    <label>
        Slaptažodis:
        <input type="password" name="password" required>
    </label>

    <button type="submit">Prisijungti</button>
</form>

    <h2>Demo prisijungimai</h2>
    <ul>
        <li>Studentas: <code>student@example.com</code> / <code>student123</code></li>
        <li>Administratorius: <code>admin@example.com</code> / <code>admin123</code></li>
    </ul>

<p>Neturite paskyros? <a href="/register">Registracija</a></p>

</body>
</html>
