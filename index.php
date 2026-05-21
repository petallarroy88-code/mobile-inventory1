<?php
// index.php — Dashboard
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();
$conn = getDBConnection();

// Stats
$totalProducts  = $conn->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetch_row()[0];
$totalCategories= $conn->query("SELECT COUNT(*) FROM categories WHERE is_active=1")->fetch_row()[0];
$totalSuppliers = $conn->query("SELECT COUNT(*) FROM suppliers WHERE is_active=1")->fetch_row()[0];
$lowStockCount  = $conn->query("SELECT COUNT(*) FROM products WHERE is_active=1 AND stock_quantity <= low_stock_threshold")->fetch_row()[0];
$outOfStock     = $conn->query("SELECT COUNT(*) FROM products WHERE is_active=1 AND stock_quantity = 0")->fetch_row()[0];
$inventoryValue = $conn->query("SELECT SUM(cost_price * stock_quantity) FROM products WHERE is_active=1")->fetch_row()[0] ?? 0;

// Low stock products
$lowStockProducts = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active=1 AND p.stock_quantity <= p.low_stock_threshold
    ORDER BY p.stock_quantity ASC LIMIT 10
");

// Recent movements
$recentMovements = $conn->query("
    SELECT sm.*, p.name AS product_name, u.full_name AS user_name
    FROM stock_movements sm
    LEFT JOIN products p ON sm.product_id = p.id
    LEFT JOIN users u ON sm.user_id = u.id
    ORDER BY sm.created_at DESC LIMIT 8
");

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-boxes"></i></div>
        <div>
            <div class="stat-value"><?= number_format($totalProducts) ?></div>
            <div class="stat-label">Total Products</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-peso-sign"></i></div>
        <div>
            <div class="stat-value"><?= formatCurrency($inventoryValue) ?></div>
            <div class="stat-label">Inventory Value</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <div class="stat-value"><?= $lowStockCount ?></div>
            <div class="stat-label">Low Stock Items</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-ban"></i></div>
        <div>
            <div class="stat-value"><?= $outOfStock ?></div>
            <div class="stat-label">Out of Stock</div>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; flex-wrap:wrap;">

<!-- Low Stock Alerts -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-exclamation-triangle" style="color:var(--warning)"></i> Low Stock Alerts</h2>
        <a href="<?= BASE_URL ?>modules/products/index.php?filter=low_stock" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="table-responsive">
    <table>
        <thead>
            <tr><th>Product</th><th>Category</th><th>Stock</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php if ($lowStockProducts->num_rows === 0): ?>
            <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No low stock items</td></tr>
        <?php else: while ($row = $lowStockProducts->fetch_assoc()): ?>
            <tr>
                <td><strong><?= sanitize($row['name']) ?></strong><br><small style="color:var(--text-muted)"><?= sanitize($row['sku']) ?></small></td>
                <td><?= sanitize($row['category_name'] ?? '—') ?></td>
                <td><strong><?= $row['stock_quantity'] ?></strong></td>
                <td><?= stockBadge($row['stock_quantity'], $row['low_stock_threshold']) ?></td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Recent Stock Movements -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-history" style="color:var(--primary)"></i> Recent Movements</h2>
    </div>
    <div class="table-responsive">
    <table>
        <thead>
            <tr><th>Product</th><th>Type</th><th>Qty</th><th>Date</th></tr>
        </thead>
        <tbody>
        <?php if ($recentMovements->num_rows === 0): ?>
            <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No movements yet</td></tr>
        <?php else: while ($row = $recentMovements->fetch_assoc()): ?>
            <tr>
                <td><?= sanitize($row['product_name'] ?? '—') ?></td>
                <td>
                    <?php
                    $typeMap = ['in' => 'badge-success', 'out' => 'badge-danger', 'adjustment' => 'badge-info', 'return' => 'badge-warning'];
                    $cls = $typeMap[$row['movement_type']] ?? 'badge-secondary';
                    ?>
                    <span class="badge <?= $cls ?>"><?= ucfirst($row['movement_type']) ?></span>
                </td>
                <td><?= ($row['movement_type'] === 'in' ? '+' : '-') . abs($row['quantity']) ?></td>
                <td style="font-size:12px; color:var(--text-muted)"><?= formatDate($row['created_at'], 'M d, h:i A') ?></td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>
</div>

</div><!-- grid -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
