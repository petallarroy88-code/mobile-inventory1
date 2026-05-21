<?php
// modules/reports/index.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();
$conn = getDBConnection();

$report = $_GET['report'] ?? 'inventory';

// Inventory Summary by Category
$byCategory = $conn->query("
    SELECT c.name AS category_name, COUNT(p.id) AS total_products,
           SUM(p.stock_quantity) AS total_stock,
           SUM(p.cost_price * p.stock_quantity) AS total_cost_value,
           SUM(p.selling_price * p.stock_quantity) AS total_sell_value
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id AND p.is_active=1
    WHERE c.is_active=1
    GROUP BY c.id ORDER BY total_sell_value DESC
");

// Low stock
$lowStock = $conn->query("
    SELECT p.*, c.name AS category_name, s.name AS supplier_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE p.is_active=1 AND p.stock_quantity <= p.low_stock_threshold
    ORDER BY p.stock_quantity ASC
");

// Top products by value
$topByValue = $conn->query("
    SELECT p.name, p.sku, p.stock_quantity, p.selling_price,
           (p.selling_price * p.stock_quantity) AS total_value
    FROM products p WHERE p.is_active=1
    ORDER BY total_value DESC LIMIT 10
");

// Recent stock movements summary
$movementsSummary = $conn->query("
    SELECT DATE(created_at) AS move_date,
           SUM(CASE WHEN movement_type='in' THEN quantity ELSE 0 END) AS stock_in,
           SUM(CASE WHEN movement_type='out' THEN quantity ELSE 0 END) AS stock_out
    FROM stock_movements
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY move_date ORDER BY move_date DESC LIMIT 14
");

$pageTitle = 'Reports';
$activePage = 'reports';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap">
    <a href="?report=inventory" class="btn <?= $report==='inventory' ? 'btn-primary' : 'btn-outline' ?>"><i class="fas fa-boxes"></i> Inventory Summary</a>
    <a href="?report=low_stock" class="btn <?= $report==='low_stock' ? 'btn-primary' : 'btn-outline' ?>"><i class="fas fa-exclamation-triangle"></i> Low Stock</a>
    <a href="?report=top_value" class="btn <?= $report==='top_value' ? 'btn-primary' : 'btn-outline' ?>"><i class="fas fa-trophy"></i> Top by Value</a>
    <a href="?report=movements" class="btn <?= $report==='movements' ? 'btn-primary' : 'btn-outline' ?>"><i class="fas fa-history"></i> Stock Movements</a>
</div>

<?php if ($report === 'inventory'): ?>
<div class="card">
    <div class="card-header"><h2 class="card-title">Inventory by Category</h2></div>
    <div class="table-responsive">
    <table>
        <thead><tr><th>Category</th><th>Products</th><th>Total Stock</th><th>Cost Value</th><th>Sell Value</th><th>Potential Profit</th></tr></thead>
        <tbody>
        <?php $grandSell=0; $grandCost=0; while ($row = $byCategory->fetch_assoc()):
            $grandSell += $row['total_sell_value']; $grandCost += $row['total_cost_value']; ?>
        <tr>
            <td><strong><?= sanitize($row['category_name']) ?></strong></td>
            <td><?= $row['total_products'] ?></td>
            <td><?= number_format($row['total_stock']) ?></td>
            <td><?= formatCurrency($row['total_cost_value']) ?></td>
            <td><?= formatCurrency($row['total_sell_value']) ?></td>
            <td style="color:var(--success)"><?= formatCurrency($row['total_sell_value'] - $row['total_cost_value']) ?></td>
        </tr>
        <?php endwhile; ?>
        <tr style="font-weight:700; background:var(--bg)">
            <td>TOTAL</td><td></td><td></td>
            <td><?= formatCurrency($grandCost) ?></td>
            <td><?= formatCurrency($grandSell) ?></td>
            <td style="color:var(--success)"><?= formatCurrency($grandSell - $grandCost) ?></td>
        </tr>
        </tbody>
    </table>
    </div>
</div>

<?php elseif ($report === 'low_stock'): ?>
<div class="card">
    <div class="card-header"><h2 class="card-title">Low Stock Report</h2></div>
    <div class="table-responsive">
    <table>
        <thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>Supplier</th><th>Current Stock</th><th>Threshold</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($row = $lowStock->fetch_assoc()): ?>
        <tr>
            <td><code><?= sanitize($row['sku']) ?></code></td>
            <td><?= sanitize($row['name']) ?></td>
            <td><?= sanitize($row['category_name'] ?? '—') ?></td>
            <td><?= sanitize($row['supplier_name'] ?? '—') ?></td>
            <td><strong><?= $row['stock_quantity'] ?></strong></td>
            <td><?= $row['low_stock_threshold'] ?></td>
            <td><?= stockBadge($row['stock_quantity'], $row['low_stock_threshold']) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<?php elseif ($report === 'top_value'): ?>
<div class="card">
    <div class="card-header"><h2 class="card-title">Top 10 Products by Inventory Value</h2></div>
    <div class="table-responsive">
    <table>
        <thead><tr><th>Rank</th><th>SKU</th><th>Product</th><th>Stock</th><th>Unit Price</th><th>Total Value</th></tr></thead>
        <tbody>
        <?php $rank=0; while ($row = $topByValue->fetch_assoc()): $rank++; ?>
        <tr>
            <td><strong>#<?= $rank ?></strong></td>
            <td><code><?= sanitize($row['sku']) ?></code></td>
            <td><?= sanitize($row['name']) ?></td>
            <td><?= $row['stock_quantity'] ?></td>
            <td><?= formatCurrency($row['selling_price']) ?></td>
            <td style="font-weight:600;color:var(--primary)"><?= formatCurrency($row['total_value']) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<?php elseif ($report === 'movements'): ?>
<div class="card">
    <div class="card-header"><h2 class="card-title">Stock Movements — Last 30 Days</h2></div>
    <div class="table-responsive">
    <table>
        <thead><tr><th>Date</th><th>Stock In</th><th>Stock Out</th><th>Net</th></tr></thead>
        <tbody>
        <?php while ($row = $movementsSummary->fetch_assoc()):
            $net = $row['stock_in'] - $row['stock_out']; ?>
        <tr>
            <td><?= formatDate($row['move_date']) ?></td>
            <td style="color:var(--success)">+<?= $row['stock_in'] ?></td>
            <td style="color:var(--danger)">-<?= $row['stock_out'] ?></td>
            <td style="font-weight:600; color:<?= $net >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
                <?= ($net >= 0 ? '+' : '') . $net ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
