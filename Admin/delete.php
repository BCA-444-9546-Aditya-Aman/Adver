<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../db_connect.php';

// Get current admin record
try {
    $ca_stmt = $pdo->prepare("SELECT is_super_admin FROM admin_users WHERE username = ?");
    $ca_stmt->execute([$_SESSION['admin_username']]);
    $current_admin = $ca_stmt->fetch();
    $is_super_admin = $current_admin ? (bool)$current_admin['is_super_admin'] : false;
} catch (\PDOException $e) {
    $is_super_admin = true;
}

if (!$is_super_admin) {
    header('Location: dashboard.php');
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$id   = isset($_GET['id'])   ? intval($_GET['id']) : 0;

if ($id > 0 && in_array($type, ['web', 'seo', 'smm', 'automation'])) {
    $table = "{$type}_leads";
    try {
        $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
        $_SESSION['msg'] = ucfirst($type) . " lead deleted successfully.";
    } catch (\PDOException $e) {
        $_SESSION['msg'] = "Error deleting lead.";
    }
    header("Location: leads_{$type}.php");
    exit;
} else {
    header('Location: dashboard.php');
    exit;
}
