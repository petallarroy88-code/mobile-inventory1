<?php
// modules/suppliers/index.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();
$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supId         = (int)($_POST['id'] ?? 0);
    $name          = trim($_POST['name'] ?? '');
    $contactPerson = trim($_POST['contact_person'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $notes         = trim($_POST['notes'] ?? '');

    if ($name) {
        if ($supId) {
            $conn->prepare("UPDATE suppliers SET name=?,contact_person=?,email=?,phone=?,address=?,city=?,notes=? WHERE id=?")
                 ->execute_query([$name,$contactPerson,$email,$phone,$address,$city,$notes,$supId]);
            flashMessage('success', 'Supplier updated.');
        } else {
            $conn->prepare("INSERT INTO suppliers (name,contact_person,email,phone,address,city,notes) VALUES (?,?,?,?,?,?,?)")
                 ->execute_query([$name,$contactPerson,$email,$phone,$address,$city,$notes]);
            flashMessage('success', 'Supplier added.');
        }
    }
    header('Location: index.php');
    exit;
}

if (isset($_GET['delete'])) {
    requireRole('admin','manager');
    $conn->query("UPDATE suppliers SET is_active=0 WHERE id=" . (int)$_GET['delete']);
    flashMessage('success', 'Supplier removed.');
    header('Location: index.php');
    exit;
}

$suppliers = $conn->query("
    SELECT s.*, COUNT(p.id) AS product_count
    FROM suppliers s
    LEFT JOIN products p ON p.supplier_id = s.id AND p.is_active=1
    WHERE s.is_active=1
    GROUP BY s.id
    ORDER BY s.name
");

$editSup = null;
if (isset($_GET['edit'])) {
    $editSup = $conn->query("SELECT * FROM suppliers WHERE id=" . (int)$_GET['edit'])->fetch_assoc();
}

$pageTitle = 'Suppliers';
$activePage = 'suppliers';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display:grid; grid-template-columns:1fr 1.5fr; gap:24px">

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= $editSup ? 'Edit Supplier' : 'Add Supplier' ?></h2>
        <?php if ($editSup): ?><a href="index.php" class="btn btn-sm btn-secondary">Cancel</a><?php endif; ?>
    </div>
    <div class="card-body">
        <form method="POST">
            <?php if ($editSup): ?><input type="hidden" name="id" value="<?= $editSup['id'] ?>"><?php endif; ?>
            <div class="form-group"><label>Company Name *</label><input type="text" name="name" value="<?= sanitize($editSup['name'] ?? '') ?>" required></div>
            <div class="form-group"><label>Contact Person</label><input type="text" name="contact_person" value="<?= sanitize($editSup['contact_person'] ?? '') ?>"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= sanitize($editSup['email'] ?? '') ?>"></div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= sanitize($editSup['phone'] ?? '') ?>"></div>
            <div class="form-group"><label>City</label><input type="text" name="city" value="<?= sanitize($editSup['city'] ?? '') ?>"></div>
            <div class="form-group"><label>Address</label><textarea name="address" rows="2"><?= sanitize($editSup['address'] ?? '') ?></textarea></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" rows="2"><?= sanitize($editSup['notes'] ?? '') ?></textarea></div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $editSup ? 'Update' : 'Save' ?> Supplier</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">Suppliers</h2></div>
    <div class="table-responsive">
    <table>
        <thead><tr><th>Supplier</th><th>Contact</th><th>Products</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($s = $suppliers->fetch_assoc()): ?>
        <tr>
            <td>
                <strong><?= sanitize($s['name']) ?></strong>
                <?php if ($s['city']): ?><br><small style="color:var(--text-muted)"><?= sanitize($s['city']) ?></small><?php endif; ?>
            </td>
            <td>
                <?php if ($s['contact_person']): ?><div><?= sanitize($s['contact_person']) ?></div><?php endif; ?>
                <?php if ($s['phone']): ?><small style="color:var(--text-muted)"><?= sanitize($s['phone']) ?></small><?php endif; ?>
            </td>
            <td><span class="badge badge-info"><?= $s['product_count'] ?></span></td>
            <td>
                <div style="display:flex; gap:4px">
                    <a href="?edit=<?= $s['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                    <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Remove this supplier?"><i class="fas fa-trash"></i></a>
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
