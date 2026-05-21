<?php
// modules/categories/index.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();
$conn = getDBConnection();

// Handle create/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catId  = (int)($_POST['id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $icon   = trim($_POST['icon'] ?? 'box');
    $slug   = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));

    if ($name) {
        if ($catId) {
            $conn->prepare("UPDATE categories SET name=?,slug=?,description=?,icon=? WHERE id=?")
                 ->execute_query([$name, $slug, $desc, $icon, $catId]);
            flashMessage('success', 'Category updated.');
        } else {
            $conn->prepare("INSERT INTO categories (name,slug,description,icon) VALUES (?,?,?,?)")
                 ->execute_query([$name, $slug, $desc, $icon]);
            flashMessage('success', 'Category created.');
        }
    }
    header('Location: index.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    requireRole('admin', 'manager');
    $conn->query("UPDATE categories SET is_active=0 WHERE id=" . (int)$_GET['delete']);
    flashMessage('success', 'Category deleted.');
    header('Location: index.php');
    exit;
}

$categories = $conn->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id AND p.is_active=1
    WHERE c.is_active=1
    GROUP BY c.id
    ORDER BY c.name
");

$editCat = null;
if (isset($_GET['edit'])) {
    $editCat = $conn->query("SELECT * FROM categories WHERE id=" . (int)$_GET['edit'])->fetch_assoc();
}

$pageTitle = 'Categories';
$activePage = 'categories';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display:grid; grid-template-columns:1fr 1.5fr; gap:24px">

<!-- Form -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= $editCat ? 'Edit Category' : 'Add Category' ?></h2>
        <?php if ($editCat): ?><a href="index.php" class="btn btn-sm btn-secondary">Cancel</a><?php endif; ?>
    </div>
    <div class="card-body">
        <form method="POST">
            <?php if ($editCat): ?><input type="hidden" name="id" value="<?= $editCat['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label>Category Name *</label>
                <input type="text" name="name" value="<?= sanitize($editCat['name'] ?? '') ?>" required placeholder="e.g. Screen Protectors">
            </div>
            <div class="form-group">
                <label>Icon (Font Awesome name)</label>
                <input type="text" name="icon" value="<?= sanitize($editCat['icon'] ?? 'box') ?>" placeholder="box, shield, zap, battery…">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Short description…"><?= sanitize($editCat['description'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= $editCat ? 'Update' : 'Save' ?> Category
            </button>
        </form>
    </div>
</div>

<!-- List -->
<div class="card">
    <div class="card-header"><h2 class="card-title">All Categories (<?= $categories->num_rows ?>)</h2></div>
    <div class="table-responsive">
    <table>
        <thead><tr><th>Category</th><th>Products</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($cat = $categories->fetch_assoc()): ?>
        <tr>
            <td>
                <div style="display:flex; align-items:center; gap:10px">
                    <div style="width:36px;height:36px;background:var(--primary-light);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--primary)">
                        <i class="fas fa-<?= sanitize($cat['icon']) ?>"></i>
                    </div>
                    <div>
                        <strong><?= sanitize($cat['name']) ?></strong>
                        <?php if ($cat['description']): ?><br><small style="color:var(--text-muted)"><?= sanitize(substr($cat['description'],0,50)) ?>…</small><?php endif; ?>
                    </div>
                </div>
            </td>
            <td><span class="badge badge-info"><?= $cat['product_count'] ?> items</span></td>
            <td>
                <div style="display:flex; gap:4px">
                    <a href="?edit=<?= $cat['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                    <a href="?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Delete this category?"><i class="fas fa-trash"></i></a>
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
