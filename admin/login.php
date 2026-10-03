<?php
require_once __DIR__ . '/../config/config.php';

if (is_admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $error = 'Too many failed attempts. Try again after ' . date('g:i A', strtotime($user['locked_until'])) . '.';
    } elseif ($user && password_verify($password, $user['password_hash'])) {
        // Success — reset throttling, rotate session id to prevent fixation.
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['last_activity'] = time();

        $upd = $pdo->prepare('UPDATE admin_users SET failed_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = :id');
        $upd->execute([':id' => $user['id']]);

        header('Location: dashboard.php');
        exit;
    } else {
        // Generic message — never reveal whether the username exists.
        $error = 'Invalid username or password.';

        if ($user) {
            $attempts = $user['failed_attempts'] + 1;
            $lockUntil = null;
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60);
            }
            $upd = $pdo->prepare('UPDATE admin_users SET failed_attempts = :a, locked_until = :l WHERE id = :id');
            $upd->execute([':a' => $attempts, ':l' => $lockUntil, ':id' => $user['id']]);
        }
        // Small delay slows down brute-force scripts without harming real users.
        usleep(400000);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>
<meta name="robots" content="noindex, nofollow">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Fraunces:wght@600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;">
<div class="admin-card" style="width:360px;">
  <h2 class="display" style="margin-bottom:20px;">Dashboard login</h2>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="POST">
    <?= csrf_field() ?>
    <div class="form-group"><label>Username</label><input type="text" name="username" required autofocus></div>
    <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
    <button type="submit" class="btn btn-primary" style="width:100%;">Log in</button>
  </form>
</div>
</body>
</html>
