<?php
// register.php — Create Account (Admin approval or open registration)
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// If already logged in, go to dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$errors = [];
$success = false;
$data = [
    'full_name' => '',
    'username'  => '',
    'email'     => '',
    'role'      => 'staff',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['full_name'] = trim($_POST['full_name'] ?? '');
    $data['username']  = trim($_POST['username'] ?? '');
    $data['email']     = trim($_POST['email'] ?? '');
    $data['role']      = 'staff'; // new accounts are always staff by default
    $password          = $_POST['password'] ?? '';
    $confirmPassword   = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($data['full_name']))
        $errors[] = 'Full name is required.';

    if (empty($data['username']))
        $errors[] = 'Username is required.';
    elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $data['username']))
        $errors[] = 'Username must be 3–30 characters (letters, numbers, underscore only).';

    if (empty($data['email']))
        $errors[] = 'Email address is required.';
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = 'Please enter a valid email address.';

    if (empty($password))
        $errors[] = 'Password is required.';
    elseif (strlen($password) < 6)
        $errors[] = 'Password must be at least 6 characters.';

    if ($password !== $confirmPassword)
        $errors[] = 'Passwords do not match.';

    // Check username/email uniqueness
    if (empty($errors)) {
        $conn = getDBConnection();

        $checkUser = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkUser->bind_param('s', $data['username']);
        $checkUser->execute();
        if ($checkUser->get_result()->num_rows > 0)
            $errors[] = 'Username is already taken.';

        $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->bind_param('s', $data['email']);
        $checkEmail->execute();
        if ($checkEmail->get_result()->num_rows > 0)
            $errors[] = 'Email address is already registered.';
    }

    // Insert new user
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO users (username, password, full_name, email, role, is_active, created_at)
            VALUES (?, ?, ?, ?, 'staff', 1, NOW())
        ");
        $stmt->bind_param('ssss',
            $data['username'],
            $hashedPassword,
            $data['full_name'],
            $data['email']
        );

        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .register-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .register-card {
            background: var(--surface);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
        }
        .register-logo {
            text-align: center;
            margin-bottom: 28px;
        }
        .register-logo i {
            font-size: 36px;
            color: var(--primary);
        }
        .register-logo h1 {
            font-size: 22px;
            margin-top: 8px;
        }
        .register-logo p {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .register-card .form-group {
            margin-bottom: 14px;
        }
        .register-card .btn-primary {
            width: 100%;
            justify-content: center;
            padding: 12px;
            margin-top: 6px;
            font-size: 15px;
        }
        .divider {
            text-align: center;
            margin: 18px 0 14px;
            position: relative;
            color: var(--text-muted);
            font-size: 13px;
        }
        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 42%;
            height: 1px;
            background: var(--border);
        }
        .divider::before { left: 0; }
        .divider::after  { right: 0; }
        .login-link {
            text-align: center;
            font-size: 14px;
            color: var(--text-muted);
        }
        .login-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
        .login-link a:hover { text-decoration: underline; }
        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            padding-right: 42px;
        }
        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 15px;
        }
        .success-box {
            text-align: center;
            padding: 20px 0;
        }
        .success-box i {
            font-size: 52px;
            color: var(--success);
            margin-bottom: 14px;
            display: block;
        }
        .success-box h2 {
            font-size: 20px;
            margin-bottom: 8px;
        }
        .success-box p {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .role-note {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #0369a1;
            margin-bottom: 16px;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
        .strength-bar {
            height: 4px;
            border-radius: 4px;
            margin-top: 6px;
            background: var(--border);
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            border-radius: 4px;
            transition: width .3s, background .3s;
            width: 0%;
        }
        .strength-label {
            font-size: 11px;
            margin-top: 3px;
            color: var(--text-muted);
        }
        @media (max-width: 480px) {
            .form-row { grid-template-columns: 1fr; }
            .register-card { padding: 28px 20px; }
        }
    </style>
</head>
<body class="register-page">

<div class="register-card">

    <div class="register-logo">
        <i class="fas fa-mobile-alt"></i>
        <h1><?= APP_NAME ?></h1>
        <p>Create your account</p>
    </div>

    <?php if ($success): ?>
    <!-- Success State -->
    <div class="success-box">
        <i class="fas fa-check-circle"></i>
        <h2>Account Created!</h2>
        <p>Welcome, <strong><?= sanitize($data['full_name']) ?></strong>!<br>
           Your account has been created successfully.<br>
           You can now log in with your credentials.</p>
        <a href="<?= BASE_URL ?>login.php" class="btn btn-primary">
            <i class="fas fa-sign-in-alt"></i> Go to Login
        </a>
    </div>

    <?php else: ?>
    <!-- Registration Form -->

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-times-circle"></i>
            <div>
                <?php foreach ($errors as $e): ?>
                    <div><?= sanitize($e) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="role-note">
        <i class="fas fa-info-circle" style="margin-top:2px; flex-shrink:0"></i>
        <span>New accounts are assigned the <strong>Staff</strong> role. An admin can upgrade your role after registration.</span>
    </div>

    <form method="POST" id="registerForm" novalidate>

        <div class="form-row">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name"
                       value="<?= sanitize($data['full_name']) ?>"
                       placeholder="Juan Dela Cruz" required autofocus>
            </div>
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username"
                       value="<?= sanitize($data['username']) ?>"
                       placeholder="juandc" required
                       pattern="[a-zA-Z0-9_]{3,30}">
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email"
                   value="<?= sanitize($data['email']) ?>"
                   placeholder="juan@example.com" required>
        </div>

        <div class="form-group">
            <label for="password">Password *</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password"
                       placeholder="At least 6 characters" required minlength="6"
                       oninput="checkStrength(this.value)">
                <button type="button" class="toggle-pw" onclick="togglePw('password', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            <div class="strength-label" id="strengthLabel"></div>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password *</label>
            <div class="password-wrapper">
                <input type="password" id="confirm_password" name="confirm_password"
                       placeholder="Re-enter your password" required>
                <button type="button" class="toggle-pw" onclick="togglePw('confirm_password', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Create Account
        </button>
    </form>

    <div class="divider">or</div>
    <div class="login-link">
        Already have an account? <a href="<?= BASE_URL ?>login.php">Sign In</a>
    </div>

    <?php endif; ?>
</div>

<script>
function togglePw(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

function checkStrength(val) {
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { w: '0%',   bg: '',                    text: '' },
        { w: '25%',  bg: 'var(--danger)',        text: 'Weak' },
        { w: '50%',  bg: 'var(--warning)',       text: 'Fair' },
        { w: '75%',  bg: '#2563eb',              text: 'Good' },
        { w: '90%',  bg: 'var(--success)',       text: 'Strong' },
        { w: '100%', bg: 'var(--success)',       text: 'Very Strong' },
    ];
    const lvl = levels[Math.min(score, 5)];
    fill.style.width      = lvl.w;
    fill.style.background = lvl.bg;
    label.textContent     = lvl.text;
}
</script>

</body>
</html>
