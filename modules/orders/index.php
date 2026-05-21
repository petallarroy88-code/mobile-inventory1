<?php
// modules/orders/index.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();
$conn = getDBConnection();

// Receive a PO
if (isset($_GET['receive'])) {
    $poId = (int)$_GET['receive'];
    $po = $conn->query("SELECT * FROM purchase_orders WHERE id=$poId")->fetch_assoc();
    if ($po && $po['status'] === 'ordered') {
        $items = $conn->query("SELECT * FROM purchase_order_items WHERE po_id=$poId");
        while ($item = $items->fetch_assoc()) {
            $conn->query("UPDATE products SET stock_quantity = stock_quantity + {$item['quantity']} WHERE id={$item['product_id']}");
            $before = $conn->query("SELECT stock_quantity FROM products WHERE id={$item['product_id']}")->fetch_row()[0];
            $after = $before + $item['quantity'];
            $userId = currentUser()['id'];
            $conn->prepare("INSERT INTO stock_movements (product_id,movement_type,quantity,quantity_before,quantity_after,reference_no,user_id) VALUES (?,?,?,?,?,?,?)")
                 ->execute_query([$item['product_id'],'in',$item['quantity'],$before - $item['quantity'],$before,$po['po_number'],$userId]);
            $conn->query("UPDATE purchase_order_items SET received_qty={$item['quantity']} WHERE id={$item['id']}");
        }
        $conn->query("UPDATE purchase_orders SET status='received', received_date=NOW() WHERE id=$poId");
        flashMessage('success', 'Purchase order received and stock updated.');
    }
    header('Location: index.php');
    exit;
}

$orders = $conn->query("
    SELECT po.*, s.name AS supplier_name,
           (SELECT COUNT(*) FROM purchase_order_items WHERE po_id=po.id) AS item_count
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    ORDER BY po.created_at DESC
");

$suppliers = $conn->query("SELECT * FROM suppliers WHERE is_active=1 ORDER BY name");
$products  = $conn->query("SELECT id, name, sku, cost_price FROM products WHERE is_active=1 ORDER BY name");

$pageTitle = 'Purchase Orders';
$activePage = 'orders';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h2 class="card-title">Purchase Orders</h2>
        <button class="btn btn-primary" data-modal="newPOModal"><i class="fas fa-plus"></i> New PO</button>
    </div>
    <div class="table-responsive">
    <table>
        <thead><tr><th>PO Number</th><th>Supplier</th><th>Status</th><th>Items</th><th>Total</th><th>Order Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($po = $orders->fetch_assoc()): ?>
        <tr>
            <td><strong><?= sanitize($po['po_number']) ?></strong></td>
            <td><?= sanitize($po['supplier_name'] ?? '—') ?></td>
            <td>
                <?php
                $statusMap = ['pending'=>'badge-warning','ordered'=>'badge-info','received'=>'badge-success','cancelled'=>'badge-danger'];
                $sc = $statusMap[$po['status']] ?? 'badge-secondary';
                ?>
                <span class="badge <?= $sc ?>"><?= ucfirst($po['status']) ?></span>
            </td>
            <td><?= $po['item_count'] ?> items</td>
            <td><?= formatCurrency($po['total_amount']) ?></td>
            <td><?= formatDate($po['order_date']) ?></td>
            <td>
                <?php if ($po['status'] === 'ordered'): ?>
                <a href="?receive=<?= $po['id'] ?>" class="btn btn-sm btn-success" data-confirm="Mark as received and update stock?">
                    <i class="fas fa-check"></i> Receive
                </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- New PO Modal -->
<div class="modal-overlay" id="newPOModal">
    <div class="modal" style="max-width:700px">
        <div class="modal-header">
            <h3 class="modal-title">New Purchase Order</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="POST" action="create.php">
        <div class="modal-body">
            <div class="form-grid">
                <div class="form-group">
                    <label>Supplier</label>
                    <select name="supplier_id">
                        <option value="">— Select —</option>
                        <?php while ($s = $suppliers->fetch_assoc()): ?>
                        <option value="<?= $s['id'] ?>"><?= sanitize($s['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Expected Date</label>
                    <input type="date" name="expected_date">
                </div>
                <div class="form-group full">
                    <label>Notes</label>
                    <textarea name="notes" rows="2"></textarea>
                </div>
            </div>
            <div style="margin-top:16px">
                <label style="font-weight:600">Products to Order</label>
                <div id="poItems" style="margin-top:8px">
                    <div class="po-item" style="display:grid; grid-template-columns:2fr 1fr 1fr auto; gap:8px; margin-bottom:8px; align-items:center">
                        <select name="product_id[]">
                            <option value="">— Product —</option>
                            <?php $products->data_seek(0); while ($pr = $products->fetch_assoc()): ?>
                            <option value="<?= $pr['id'] ?>" data-cost="<?= $pr['cost_price'] ?>"><?= sanitize($pr['name']) ?> (<?= sanitize($pr['sku']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                        <input type="number" name="quantity[]" placeholder="Qty" min="1">
                        <input type="number" step="0.01" name="unit_cost[]" placeholder="Cost ₱" min="0">
                        <button type="button" onclick="this.closest('.po-item').remove()" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline" id="addPOItem"><i class="fas fa-plus"></i> Add Item</button>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary modal-close">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create PO</button>
        </div>
        </form>
    </div>
</div>

<script>
const productOptions = `<?php
$products->data_seek(0);
$opts = '<option value="">— Product —</option>';
while ($pr = $products->fetch_assoc()) {
    $opts .= '<option value="'.$pr['id'].'" data-cost="'.$pr['cost_price'].'">'.htmlspecialchars($pr['name']).' ('.$pr['sku'].')</option>';
}
echo $opts;
?>`;

document.getElementById('addPOItem').addEventListener('click', () => {
    const div = document.createElement('div');
    div.className = 'po-item';
    div.style.cssText = 'display:grid; grid-template-columns:2fr 1fr 1fr auto; gap:8px; margin-bottom:8px; align-items:center';
    div.innerHTML = `
        <select name="product_id[]">${productOptions}</select>
        <input type="number" name="quantity[]" placeholder="Qty" min="1">
        <input type="number" step="0.01" name="unit_cost[]" placeholder="Cost ₱" min="0">
        <button type="button" onclick="this.closest('.po-item').remove()" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></button>
    `;
    document.getElementById('poItems').appendChild(div);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
