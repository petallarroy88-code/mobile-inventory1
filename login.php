<?php
// login.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter your username and password.';
    } elseif (login($username, $password)) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="login-page">
<div class="login-card">
    <div class="login-logo">
        <i class="fas fa-mobile-alt"></i>
        <h1><?= APP_NAME ?></h1>
        <p>Mobile Accessories Inventory System</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-times-circle"></i><?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Enter your username"
                   value="<?= sanitize($_POST['username'] ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
    </form>
    <p style="text-align:center; margin-top:16px; font-size:12px; color:var(--text-muted);">
        Default: <strong>admin</strong> / <strong>admin123</strong>
    </p>
    <div style="text-align:center; margin-top:16px; padding-top:16px; border-top:1px solid var(--border); font-size:14px; color:var(--text-muted);">
        Don't have an account?
        <a href="<?= BASE_URL ?>register.php" style="color:var(--primary); font-weight:600; text-decoration:none;">
            Create Account
        </a>
    </div>
</div>
</body>
</html>
