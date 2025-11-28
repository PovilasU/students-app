<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Registracija</title>
    <link rel="stylesheet" href="/css/water.css">
</head>
<body>

<h1>Paraiškų sistema – registracija</h1>

<?php if (!empty($error)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="/register">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

    <div>
        <label>
            Vardas:
            <input type="text" name="name" required>
        </label>
    </div>

    <div>
        <label>
            El. paštas:
            <input type="email" name="email" required>
        </label>
    </div>

    <div>
        <label>
            Slaptažodis:
            <input type="password" name="password" required>
        </label>
    </div>

    <div>
        <label>
            Pakartokite slaptažodį:
            <input type="password" name="password_confirm" required>
        </label>
    </div>

    <div>
        <button type="submit">Registruotis</button>
    </div>
</form>

<p>Jau turite paskyrą? <a href="/login">Prisijungti</a></p>

</body>
</html>
