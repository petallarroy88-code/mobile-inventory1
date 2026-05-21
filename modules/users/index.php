<?php
// modules/users/index.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('admin');
$conn = getDBConnection();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId    = (int)($_POST['id'] ?? 0);
    $username  = trim($_POST['username'] ?? '');
    $fullName  = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = $_POST['role'] ?? 'staff';
    $password  = $_POST['password'] ?? '';

    if (!$username || !$fullName || !$email) {
        $errors[] = 'Name, username, and email are required.';
    }

    if (empty($errors)) {
        if ($userId) {
            if ($password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $conn->prepare("UPDATE users SET username=?,full_name=?,email=?,role=?,password=? WHERE id=?")
                     ->execute_query([$username,$fullName,$email,$role,$hash,$userId]);
            } else {
                $conn->prepare("UPDATE users SET username=?,full_name=?,email=?,role=? WHERE id=?")
                     ->execute_query([$username,$fullName,$email,$role,$userId]);
            }
            flashMessage('success', 'User updated.');
        } else {
            if (!$password) $errors[] = 'Password is required for new users.';
            else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $conn->prepare("INSERT INTO users (username,full_name,email,role,password) VALUES (?,?,?,?,?)")
                     ->execute_query([$username,$fullName,$email,$role,$hash]);
                flashMessage('success', 'User created.');
            }
        }
        if (empty($errors)) { header('Location: index.php'); exit; }
    }
}

if (isset($_GET['toggle'])) {
    $uid = (int)$_GET['toggle'];
    $conn->query("UPDATE users SET is_active = NOT is_active WHERE id=$uid AND id != " . currentUser()['id']);
    header('Location: index.php');
    exit;
}

$users   = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$editUser = null;
if (isset($_GET['edit'])) {
    $editUser = $conn->query("SELECT * FROM users WHERE id=" . (int)$_GET['edit'])->fetch_assoc();
}

$pageTitle = 'User Management';
$activePage = 'users';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display:grid; grid-template-columns:1fr 1.5fr; gap:24px">

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= $editUser ? 'Edit User' : 'Add User' ?></h2>
        <?php if ($editUser): ?><a href="index.php" class="btn btn-sm btn-secondary">Cancel</a><?php endif; ?>
    </div>
    <div class="card-body">
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger"><i class="fas fa-times-circle"></i><?= sanitize($e) ?></div>
        <?php endforeach; ?>
        <form method="POST">
            <?php if ($editUser): ?><input type="hidden" name="id" value="<?= $editUser['id'] ?>"><?php endif; ?>
            <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" value="<?= sanitize($editUser['full_name'] ?? '') ?>" required></div>
            <div class="form-group"><label>Username *</label><input type="text" name="username" value="<?= sanitize($editUser['username'] ?? '') ?>" required></div>
            <div class="form-group"><label>Email *</label><input type="email" name="email" value="<?= sanitize($editUser['email'] ?? '') ?>" required></div>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <?php foreach (['admin','manager','staff'] as $r): ?>
                    <option value="<?= $r ?>" <?= ($editUser['role'] ?? 'staff') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Password <?= $editUser ? '(leave blank to keep)' : '*' ?></label>
                <input type="password" name="password" <?= $editUser ? '' : 'required' ?>>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $editUser ? 'Update' : 'Create' ?> User</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">System Users</h2></div>
    <div class="table-responsive">
    <table>
        <thead><tr><th>User</th><th>Role</th><th>Last Login</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($u = $users->fetch_assoc()): ?>
        <tr>
            <td>
                <div style="display:flex; align-items:center; gap:10px">
                    <div class="user-avatar" style="background:<?= $u['role']==='admin' ? 'var(--danger)' : ($u['role']==='manager' ? 'var(--warning)' : 'var(--primary)') ?>"><?= strtoupper(substr($u['full_name'],0,1)) ?></div>
                    <div>
                        <strong><?= sanitize($u['full_name']) ?></strong>
                        <br><small style="color:var(--text-muted)">@<?= sanitize($u['username']) ?></small>
                    </div>
                </div>
            </td>
            <td><span class="badge <?= $u['role']==='admin' ? 'badge-danger' : ($u['role']==='manager' ? 'badge-warning' : 'badge-info') ?>"><?= ucfirst($u['role']) ?></span></td>
            <td style="font-size:12px"><?= $u['last_login'] ? formatDate($u['last_login'], 'M d, Y') : 'Never' ?></td>
            <td><span class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-secondary' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td>
                <div style="display:flex; gap:4px">
                    <a href="?edit=<?= $u['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                    <?php if ($u['id'] != currentUser()['id']): ?>
                    <a href="?toggle=<?= $u['id'] ?>" class="btn btn-sm btn-secondary" title="Toggle status"><i class="fas fa-power-off"></i></a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
