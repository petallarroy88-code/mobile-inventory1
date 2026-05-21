<?php
// modules/products/view.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();
$conn = getDBConnection();

$id = (int)($_GET['id'] ?? 0);
$p = $conn->query("
    SELECT p.*, c.name AS category_name, s.name AS supplier_name, s.phone AS supplier_phone
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE p.id = $id
")->fetch_assoc();

if (!$p) { flashMessage('danger', 'Product not found.'); header('Location: index.php'); exit; }

$pageTitle = $p['name'];
$activePage = 'products';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap">
    <a href="index.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</a>
    <a href="stock.php?id=<?= $id ?>" class="btn btn-success btn-sm"><i class="fas fa-exchange-alt"></i> Stock Movement</a>
</div>

<div style="display:grid; grid-template-columns:1fr 2fr; gap:24px">

<div class="card">
    <div class="card-body" style="text-align:center">
        <?php if ($p['image']): ?>
            <img src="<?= UPLOAD_URL . sanitize($p['image']) ?>" alt="" style="width:100%;max-width:200px;border-radius:12px;margin-bottom:12px">
        <?php else: ?>
            <div style="width:100%;aspect-ratio:1;background:var(--bg);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;font-size:60px;color:var(--border)">
                <i class="fas fa-image"></i>
            </div>
        <?php endif; ?>
        <div style="font-size:22px;font-weight:700"><?= sanitize($p['name']) ?></div>
        <code style="font-size:13px;color:var(--text-muted)"><?= sanitize($p['sku']) ?></code>
        <div style="margin-top:12px"><?= stockBadge($p['stock_quantity'], $p['low_stock_threshold']) ?></div>
        <div style="margin-top:16px;font-size:32px;font-weight:800;color:var(--primary)"><?= $p['stock_quantity'] ?></div>
        <div style="color:var(--text-muted);font-size:13px">units in stock</div>
    </div>
</div>

<div>
    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h2 class="card-title">Product Details</h2></div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
                <?php
                $fields = [
                    'Brand' => $p['brand'],
                    'Model' => $p['model'],
                    'Category' => $p['category_name'],
                    'Supplier' => $p['supplier_name'],
                    'Compatible Phones' => $p['compatible_phones'],
                    'Barcode' => $p['barcode'],
                    'Low Stock Alert At' => $p['low_stock_threshold'] . ' units',
                    'Added On' => formatDate($p['created_at']),
                ];
                foreach ($fields as $label => $val): ?>
                <div>
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted)"><?= $label ?></div>
                    <div style="font-size:14px;margin-top:2px"><?= sanitize($val ?: '—') ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($p['description']): ?>
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted)">Description</div>
                <p style="margin-top:6px;font-size:14px;line-height:1.6"><?= nl2br(sanitize($p['description'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="card-title">Pricing</h2></div>
        <div class="card-body" style="display:flex; gap:24px; flex-wrap:wrap">
            <div style="flex:1; background:var(--bg); border-radius:10px; padding:16px; text-align:center">
                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase">Cost Price</div>
                <div style="font-size:24px;font-weight:700;margin-top:4px"><?= formatCurrency($p['cost_price']) ?></div>
            </div>
            <div style="flex:1; background:var(--primary-light); border-radius:10px; padding:16px; text-align:center">
                <div style="font-size:12px;color:var(--primary);text-transform:uppercase">Selling Price</div>
                <div style="font-size:24px;font-weight:700;margin-top:4px;color:var(--primary)"><?= formatCurrency($p['selling_price']) ?></div>
            </div>
            <div style="flex:1; background:var(--success-light); border-radius:10px; padding:16px; text-align:center">
                <div style="font-size:12px;color:var(--success);text-transform:uppercase">Margin</div>
                <div style="font-size:24px;font-weight:700;margin-top:4px;color:var(--success)"><?= formatCurrency($p['selling_price'] - $p['cost_price']) ?></div>
            </div>
        </div>
    </div>
</div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
