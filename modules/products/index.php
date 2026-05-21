<?php
// modules/products/index.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();
$conn = getDBConnection();

// Filters
$search = trim($_GET['search'] ?? '');
$categoryId = (int)($_GET['category'] ?? 0);
$filter = $_GET['filter'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$where = ["p.is_active = 1"];
$params = [];
$types = '';

if ($search) {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s]);
    $types .= 'sss';
}
if ($categoryId) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
    $types .= 'i';
}
if ($filter === 'low_stock') {
    $where[] = "p.stock_quantity <= p.low_stock_threshold";
}
if ($filter === 'out_of_stock') {
    $where[] = "p.stock_quantity = 0";
}

$whereSQL = implode(' AND ', $where);

// Count
$countStmt = $conn->prepare("SELECT COUNT(*) FROM products p WHERE $whereSQL");
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_row()[0];
$pager = paginate($total, $perPage, $page, '?');

// Products
$stmt = $conn->prepare("
    SELECT p.*, c.name AS category_name, s.name AS supplier_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE $whereSQL
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$limitParams = array_merge($params, [$perPage, $pager['offset']]);
$limitTypes = $types . 'ii';
$stmt->bind_param($limitTypes, ...$limitParams);
$stmt->execute();
$products = $stmt->get_result();

// Categories for filter
$categories = $conn->query("SELECT * FROM categories WHERE is_active=1 ORDER BY name");

$pageTitle = 'Products';
$activePage = 'products';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Products (<?= $total ?>)</h2>
        <div class="filter-bar">
            <form method="GET" style="display:contents">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search products…" class="live-search">
                </div>
                <select name="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php $categories->data_seek(0); while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                        <?= sanitize($cat['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <select name="filter" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="low_stock" <?= $filter === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                    <option value="out_of_stock" <?= $filter === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </form>
            <a href="<?= BASE_URL ?>modules/products/create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Product
            </a>
        </div>
    </div>

    <div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Cost</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($products->num_rows === 0): ?>
            <tr><td colspan="9" style="text-align:center; padding:40px; color:var(--text-muted)">
                <i class="fas fa-inbox" style="font-size:32px; display:block; margin-bottom:8px"></i>
                No products found
            </td></tr>
        <?php else: while ($p = $products->fetch_assoc()): ?>
            <tr>
                <td><code style="font-size:12px"><?= sanitize($p['sku']) ?></code></td>
                <td>
                    <strong><?= sanitize($p['name']) ?></strong>
                    <?php if ($p['compatible_phones']): ?>
                        <br><small style="color:var(--text-muted)"><?= sanitize($p['compatible_phones']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= sanitize($p['category_name'] ?? '—') ?></td>
                <td><?= sanitize($p['brand'] ?? '—') ?></td>
                <td><?= formatCurrency($p['cost_price']) ?></td>
                <td><?= formatCurrency($p['selling_price']) ?></td>
                <td><strong><?= $p['stock_quantity'] ?></strong></td>
                <td><?= stockBadge($p['stock_quantity'], $p['low_stock_threshold']) ?></td>
                <td>
                    <div style="display:flex; gap:4px">
                        <a href="view.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline" title="View"><i class="fas fa-eye"></i></a>
                        <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="stock.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-success" title="Stock In/Out"><i class="fas fa-exchange-alt"></i></a>
                        <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" title="Delete"
                           data-confirm="Delete this product?" ><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>

    <?php if ($pager['total_pages'] > 1): ?>
    <div style="padding:16px 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; border-top:1px solid var(--border)">
        <small style="color:var(--text-muted)">Showing <?= $pager['offset']+1 ?>–<?= min($pager['offset']+$perPage,$total) ?> of <?= $total ?></small>
        <div class="pagination">
            <?php for ($i = 1; $i <= $pager['total_pages']; $i++): ?>
            <a href="?search=<?= urlencode($search) ?>&category=<?= $categoryId ?>&filter=<?= $filter ?>&page=<?= $i ?>"
               class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
