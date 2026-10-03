<?php
/**
 * setup.php — RUN THIS ONCE to create your admin account, then DELETE this file.
 * It refuses to run again if an admin already exists, as a safety net.
 */
require_once __DIR__ . '/../config/config.php';

$existing = $pdo->query('SELECT COUNT(*) AS c FROM admin_users')->fetch();
if ($existing['c'] > 0) {
    die('An admin account already exists. For security this setup script will not run again. Please delete admin/setup.php.');
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (mb_strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    } elseif (mb_strlen($password) < 10) {
        $error = 'Password must be at least 10 characters. Use a real passphrase.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash, email) VALUES (:u, :h, :e)');
        $stmt->execute([':u' => $username, ':h' => $hash, ':e' => $email]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Setup</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;">
<div class="admin-card" style="width:380px;">
  <h2 style="margin-bottom:16px;">Create admin account</h2>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success">Admin account created. Now go delete <code>admin/setup.php</code> from your server, then <a href="login.php">log in here</a>.</div>
  <?php else: ?>
    <form method="POST">
      <?= csrf_field() ?>
      <div class="form-group"><label>Username</label><input type="text" name="username" required minlength="3"></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
      <div class="form-group"><label>Password (10+ characters)</label><input type="password" name="password" required minlength="10"></div>
      <div class="form-group"><label>Confirm password</label><input type="password" name="confirm" required minlength="10"></div>
      <button type="submit" class="btn btn-primary" style="width:100%;">Create account</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
