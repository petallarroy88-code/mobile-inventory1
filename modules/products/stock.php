<?php
// modules/products/stock.php — Stock adjustment
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();
$conn = getDBConnection();

$id = (int)($_GET['id'] ?? 0);
$product = $conn->query("SELECT * FROM products WHERE id=$id AND is_active=1")->fetch_assoc();
if (!$product) { flashMessage('danger', 'Product not found.'); header('Location: index.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type     = $_POST['movement_type'] ?? '';
    $qty      = (int)($_POST['quantity'] ?? 0);
    $notes    = trim($_POST['notes'] ?? '');
    $refNo    = trim($_POST['reference_no'] ?? '');
    $userId   = currentUser()['id'];

    $allowedTypes = ['in', 'out', 'adjustment', 'return'];
    if (!in_array($type, $allowedTypes)) $errors[] = 'Invalid movement type.';
    if ($qty <= 0) $errors[] = 'Quantity must be greater than 0.';
    if ($type === 'out' && $qty > $product['stock_quantity']) $errors[] = 'Insufficient stock.';

    if (empty($errors)) {
        $before = $product['stock_quantity'];
        if ($type === 'in' || $type === 'return') {
            $after = $before + $qty;
        } elseif ($type === 'out') {
            $after = $before - $qty;
        } else { // adjustment
            $after = $qty; // set absolute value
            $qty = abs($after - $before);
        }

        $conn->prepare("UPDATE products SET stock_quantity=? WHERE id=?")
             ->execute_query([$after, $id]);
        $conn->prepare("INSERT INTO stock_movements (product_id,movement_type,quantity,quantity_before,quantity_after,reference_no,notes,user_id) VALUES (?,?,?,?,?,?,?,?)")
             ->execute_query([$id, $type, $qty, $before, $after, $refNo ?: null, $notes ?: null, $userId]);

        flashMessage('success', 'Stock updated successfully!');
        header('Location: view.php?id=' . $id);
        exit;
    }
}

// Movement history for this product
$history = $conn->query("
    SELECT sm.*, u.full_name AS user_name
    FROM stock_movements sm
    LEFT JOIN users u ON sm.user_id = u.id
    WHERE sm.product_id = $id
    ORDER BY sm.created_at DESC LIMIT 20
");

$pageTitle = 'Stock Adjustment — ' . $product['name'];
$activePage = 'products';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom:16px">
    <a href="index.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div style="display:grid; grid-template-columns:1fr 1.5fr; gap:24px">

<div class="card">
    <div class="card-header"><h2 class="card-title">Stock Movement</h2></div>
    <div class="card-body">
        <div style="background:var(--bg); border-radius:8px; padding:16px; margin-bottom:16px;">
            <div style="font-weight:600; font-size:15px"><?= sanitize($product['name']) ?></div>
            <div style="color:var(--text-muted); font-size:13px"><?= sanitize($product['sku']) ?></div>
            <div style="margin-top:8px; font-size:24px; font-weight:700"><?= $product['stock_quantity'] ?> <span style="font-size:14px;color:var(--text-muted)">units</span></div>
            <?= stockBadge($product['stock_quantity'], $product['low_stock_threshold']) ?>
        </div>

        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger"><i class="fas fa-times-circle"></i><?= sanitize($e) ?></div>
        <?php endforeach; ?>

        <form method="POST">
            <div class="form-group">
                <label>Movement Type *</label>
                <select name="movement_type" required>
                    <option value="in">Stock In (Receive)</option>
                    <option value="out">Stock Out (Sell/Use)</option>
                    <option value="adjustment">Set Absolute Quantity</option>
                    <option value="return">Return</option>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity *</label>
                <input type="number" name="quantity" min="1" required placeholder="Enter quantity">
            </div>
            <div class="form-group">
                <label>Reference No.</label>
                <input type="text" name="reference_no" placeholder="PO#, invoice #, etc.">
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="3" placeholder="Reason or remarks…"></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-exchange-alt"></i> Update Stock</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">Movement History</h2></div>
    <div class="table-responsive">
    <table>
        <thead>
            <tr><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>By</th><th>Date</th></tr>
        </thead>
        <tbody>
        <?php while ($m = $history->fetch_assoc()): ?>
        <tr>
            <td>
                <?php
                $typeMap = ['in'=>'badge-success','out'=>'badge-danger','adjustment'=>'badge-info','return'=>'badge-warning'];
                $cls = $typeMap[$m['movement_type']] ?? 'badge-secondary';
                ?>
                <span class="badge <?= $cls ?>"><?= ucfirst($m['movement_type']) ?></span>
            </td>
            <td><?= $m['quantity'] ?></td>
            <td><?= $m['quantity_before'] ?></td>
            <td><?= $m['quantity_after'] ?></td>
            <td style="font-size:12px"><?= sanitize($m['user_name'] ?? '—') ?></td>
            <td style="font-size:12px; color:var(--text-muted)"><?= formatDate($m['created_at'], 'M d, h:i A') ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
