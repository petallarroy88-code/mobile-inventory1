<?php
// includes/auth.php

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    if (!isset($_SESSION['user_id'], $_SESSION['last_activity'])) return false;
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['user_role'], $roles)) {
        header('Location: ' . BASE_URL . 'index.php?error=unauthorized');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'name'     => $_SESSION['full_name'] ?? '',
        'role'     => $_SESSION['user_role'] ?? '',
    ];
}

function login(string $username, string $password): bool {
    require_once __DIR__ . '/../config/database.php';
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ? AND is_active = 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $row['id'];
            $_SESSION['username']   = $row['username'];
            $_SESSION['full_name']  = $row['full_name'];
            $_SESSION['user_role']  = $row['role'];
            $_SESSION['last_activity'] = time();
            // Update last login
            $conn->query("UPDATE users SET last_login = NOW() WHERE id = {$row['id']}");
            return true;
        }
    }
    return false;
}

function logout(): void {
    session_destroy();
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
