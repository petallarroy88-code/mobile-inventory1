<?php
// modules/products/delete.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('admin', 'manager');
$conn = getDBConnection();

$id = (int)($_GET['id'] ?? 0);
$conn->query("UPDATE products SET is_active=0 WHERE id=$id");
flashMessage('success', 'Product deleted.');
header('Location: index.php');
exit;
