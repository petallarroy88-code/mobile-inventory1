<?php
// includes/helpers.php

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function formatCurrency(float $amount): string {
    return '₱' . number_format($amount, 2);
}

function formatDate(string $date, string $format = 'M d, Y'): string {
    return $date ? date($format, strtotime($date)) : '—';
}

function generateSKU(string $prefix = 'PRD'): string {
    return strtoupper($prefix) . '-' . strtoupper(substr(uniqid(), -6));
}

function generatePONumber(): string {
    return 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
}

function uploadImage(array $file, string $destination): string|false {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) return false;
    if ($file['size'] > 2 * 1024 * 1024) return false; // 2MB max

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . $ext;
    $targetPath = $destination . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }
    return false;
}

function stockBadge(int $qty, int $threshold): string {
    if ($qty === 0) return '<span class="badge badge-danger">Out of Stock</span>';
    if ($qty <= $threshold) return '<span class="badge badge-warning">Low Stock</span>';
    return '<span class="badge badge-success">In Stock</span>';
}

function paginate(int $total, int $perPage, int $currentPage, string $url): array {
    $totalPages = (int) ceil($total / $perPage);
    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => ($currentPage - 1) * $perPage,
        'url'          => $url,
    ];
}

function flashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
