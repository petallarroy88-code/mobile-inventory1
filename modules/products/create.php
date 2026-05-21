<?php
// modules/products/create.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();
$conn = getDBConnection();

$errors = [];
$data = ['sku'=>'','name'=>'','description'=>'','category_id'=>'','supplier_id'=>'',
         'brand'=>'','model'=>'','compatible_phones'=>'','cost_price'=>'',
         'selling_price'=>'','stock_quantity'=>0,'low_stock_threshold'=>10,'barcode'=>''];

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
        'stock_quantity'    => (int)($_POST['stock_quantity'] ?? 0),
        'low_stock_threshold'=> (int)($_POST['low_stock_threshold'] ?? 10),
        'barcode'           => trim($_POST['barcode'] ?? ''),
    ];

    if (!$data['name'])  $errors[] = 'Product name is required.';
    if (!$data['sku'])   $errors[] = 'SKU is required.';
    if ($data['selling_price'] <= 0) $errors[] = 'Selling price must be greater than 0.';

    // Check SKU uniqueness
    $skuCheck = $conn->prepare("SELECT id FROM products WHERE sku = ?");
    $skuCheck->bind_param('s', $data['sku']);
    $skuCheck->execute();
    if ($skuCheck->get_result()->num_rows > 0) $errors[] = 'SKU already exists.';

    if (empty($errors)) {
        $imageName = null;
        if (!empty($_FILES['image']['name'])) {
            $imageName = uploadImage($_FILES['image'], UPLOAD_PATH);
            if (!$imageName) $errors[] = 'Image upload failed. Only JPG/PNG/WEBP under 2MB allowed.';
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO products
                (sku,name,description,category_id,supplier_id,brand,model,compatible_phones,
                 cost_price,selling_price,stock_quantity,low_stock_threshold,barcode,image)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sssiisssddiiss',
                $data['sku'],$data['name'],$data['description'],
                $data['category_id'],$data['supplier_id'],
                $data['brand'],$data['model'],$data['compatible_phones'],
                $data['cost_price'],$data['selling_price'],
                $data['stock_quantity'],$data['low_stock_threshold'],
                $data['barcode'],$imageName
            );
            $stmt->execute();
            $productId = $conn->insert_id;

            // Log initial stock
            if ($data['stock_quantity'] > 0) {
                $userId = currentUser()['id'];
                $conn->prepare("INSERT INTO stock_movements (product_id,movement_type,quantity,quantity_before,quantity_after,notes,user_id) VALUES (?,?,?,?,?,?,?)")
                     ->execute_query([$productId,'in',$data['stock_quantity'],0,$data['stock_quantity'],'Initial stock','$userId']);
            }

            flashMessage('success', 'Product created successfully!');
            header('Location: index.php');
            exit;
        }
    }
}

$categories = $conn->query("SELECT * FROM categories WHERE is_active=1 ORDER BY name");
$suppliers  = $conn->query("SELECT * FROM suppliers WHERE is_active=1 ORDER BY name");

$pageTitle = 'Add Product';
$activePage = 'products';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom:16px">
    <a href="index.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Products</a>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Add New Product</h2>
        <button class="btn btn-sm btn-outline" type="button" onclick="document.getElementById('skuField').value='<?= generateSKU() ?>'">
            <i class="fas fa-magic"></i> Auto SKU
        </button>
    </div>
    <div class="card-body">
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger"><i class="fas fa-times-circle"></i><?= sanitize($e) ?></div>
        <?php endforeach; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label>SKU *</label>
                    <input type="text" name="sku" id="skuField" value="<?= sanitize($data['sku']) ?>" required placeholder="e.g. CAS-001">
                </div>
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" value="<?= sanitize($data['name']) ?>" required placeholder="Full product name">
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
                <div class="form-group">
                    <label>Brand</label>
                    <input type="text" name="brand" value="<?= sanitize($data['brand']) ?>" placeholder="e.g. Spigen, Anker">
                </div>
                <div class="form-group">
                    <label>Model</label>
                    <input type="text" name="model" value="<?= sanitize($data['model']) ?>" placeholder="Model number">
                </div>
                <div class="form-group full">
                    <label>Compatible Phones</label>
                    <input type="text" name="compatible_phones" value="<?= sanitize($data['compatible_phones']) ?>" placeholder="e.g. iPhone 15, Samsung S24">
                </div>
                <div class="form-group">
                    <label>Cost Price (₱) *</label>
                    <input type="number" step="0.01" name="cost_price" value="<?= $data['cost_price'] ?>" placeholder="0.00" min="0">
                </div>
                <div class="form-group">
                    <label>Selling Price (₱) *</label>
                    <input type="number" step="0.01" name="selling_price" value="<?= $data['selling_price'] ?>" placeholder="0.00" min="0" required>
                </div>
                <div class="form-group">
                    <label>Initial Stock Quantity</label>
                    <input type="number" name="stock_quantity" value="<?= $data['stock_quantity'] ?>" min="0">
                </div>
                <div class="form-group">
                    <label>Low Stock Threshold</label>
                    <input type="number" name="low_stock_threshold" value="<?= $data['low_stock_threshold'] ?>" min="1">
                </div>
                <div class="form-group">
                    <label>Barcode</label>
                    <input type="text" name="barcode" value="<?= sanitize($data['barcode']) ?>" placeholder="Optional barcode">
                </div>
                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" accept="image/*">
                    <small style="color:var(--text-muted)">JPG/PNG/WEBP, max 2MB</small>
                </div>
                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Product description…"><?= sanitize($data['description']) ?></textarea>
                </div>
            </div>
            <div style="margin-top:20px; display:flex; gap:10px">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Product</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
