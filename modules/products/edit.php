<?php
// modules/products/edit.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();
$conn = getDBConnection();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$product = $conn->query("SELECT * FROM products WHERE id=$id")->fetch_assoc();
if (!$product) { flashMessage('danger', 'Product not found.'); header('Location: index.php'); exit; }

$errors = [];
$data = $product;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'sku'               => strtoupper(trim($_POST['sku'] ?? '')),
        'name'              => trim($_POST['name'] ?? ''),
        'description'       => trim($_POST['description'] ?? ''),
        'category_id'       => (int)($_POST['category_id'] ?? 0) ?: null,
        'supplier_id'       => (int)($_POST['supplier_id'] ?? 0) ?: null,
        'brand'             => trim($_POST['brand'] ?? ''),
        'model'             => trim($_POST['model'] ?? ''),
        'compatible_phones' => trim($_POST['compatible_phones'] ?? ''),
        'cost_price'        => (float)($_POST['cost_price'] ?? 0),
        'selling_price'     => (float)($_POST['selling_price'] ?? 0),
        'low_stock_threshold'=> (int)($_POST['low_stock_threshold'] ?? 10),
        'barcode'           => trim($_POST['barcode'] ?? ''),
        'image'             => $product['image'],
    ];

    if (!$data['name']) $errors[] = 'Product name is required.';
    if (!$data['sku'])  $errors[] = 'SKU is required.';

    // SKU uniqueness (excluding current)
    $skuCheck = $conn->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
    $skuCheck->bind_param('si', $data['sku'], $id);
    $skuCheck->execute();
    if ($skuCheck->get_result()->num_rows > 0) $errors[] = 'SKU already used by another product.';

    if (empty($errors)) {
        if (!empty($_FILES['image']['name'])) {
            $newImage = uploadImage($_FILES['image'], UPLOAD_PATH);
            if ($newImage) {
                // Delete old image
                if ($product['image'] && file_exists(UPLOAD_PATH . $product['image'])) {
                    unlink(UPLOAD_PATH . $product['image']);
                }
                $data['image'] = $newImage;
            }
        }

        $stmt = $conn->prepare("UPDATE products SET
            sku=?,name=?,description=?,category_id=?,supplier_id=?,brand=?,model=?,
            compatible_phones=?,cost_price=?,selling_price=?,low_stock_threshold=?,
            barcode=?,image=? WHERE id=?");
        $stmt->bind_param('sssiisssddissi',
            $data['sku'],$data['name'],$data['description'],
            $data['category_id'],$data['supplier_id'],
            $data['brand'],$data['model'],$data['compatible_phones'],
            $data['cost_price'],$data['selling_price'],
            $data['low_stock_threshold'],$data['barcode'],$data['image'],$id);
        $stmt->execute();

        flashMessage('success', 'Product updated successfully!');
        header('Location: view.php?id=' . $id);
        exit;
    }
}

$categories = $conn->query("SELECT * FROM categories WHERE is_active=1 ORDER BY name");
$suppliers  = $conn->query("SELECT * FROM suppliers WHERE is_active=1 ORDER BY name");

$pageTitle = 'Edit — ' . $product['name'];
$activePage = 'products';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom:16px">
    <a href="view.php?id=<?= $id ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">Edit Product</h2></div>
    <div class="card-body">
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger"><i class="fas fa-times-circle"></i><?= sanitize($e) ?></div>
        <?php endforeach; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>SKU *</label>
                    <input type="text" name="sku" value="<?= sanitize($data['sku']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" value="<?= sanitize($data['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="">— Select Category —</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>" <?= $data['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= sanitize($cat['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Supplier</label>
                    <select name="supplier_id">
                        <option value="">— Select Supplier —</option>
                        <?php while ($sup = $suppliers->fetch_assoc()): ?>
                        <option value="<?= $sup['id'] ?>" <?= $data['supplier_id'] == $sup['id'] ? 'selected' : '' ?>>
                            <?= sanitize($sup['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Brand</label><input type="text" name="brand" value="<?= sanitize($data['brand']) ?>"></div>
                <div class="form-group"><label>Model</label><input type="text" name="model" value="<?= sanitize($data['model']) ?>"></div>
                <div class="form-group full"><label>Compatible Phones</label><input type="text" name="compatible_phones" value="<?= sanitize($data['compatible_phones']) ?>"></div>
                <div class="form-group"><label>Cost Price (₱)</label><input type="number" step="0.01" name="cost_price" value="<?= $data['cost_price'] ?>" min="0"></div>
                <div class="form-group"><label>Selling Price (₱) *</label><input type="number" step="0.01" name="selling_price" value="<?= $data['selling_price'] ?>" min="0" required></div>
                <div class="form-group"><label>Low Stock Threshold</label><input type="number" name="low_stock_threshold" value="<?= $data['low_stock_threshold'] ?>" min="1"></div>
                <div class="form-group"><label>Barcode</label><input type="text" name="barcode" value="<?= sanitize($data['barcode']) ?>"></div>
                <div class="form-group">
                    <label>Product Image</label>
                    <?php if ($data['image']): ?>
                        <img src="<?= UPLOAD_URL . sanitize($data['image']) ?>" style="height:60px;border-radius:6px;margin-bottom:8px;display:block">
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*">
                </div>
                <div class="form-group full"><label>Description</label><textarea name="description" rows="3"><?= sanitize($data['description']) ?></textarea></div>
            </div>
            <div style="margin-top:20px; display:flex; gap:10px">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Product</button>
                <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
