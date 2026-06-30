<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/includes/functions.php';

// Get current admin record
try {
    $ca_stmt = $pdo->prepare("SELECT id, username, is_super_admin FROM admin_users WHERE username = ?");
    $ca_stmt->execute([$_SESSION['admin_username']]);
    $current_admin = $ca_stmt->fetch();
    $current_admin_id = $current_admin ? (int)$current_admin['id'] : 0;
    $is_super_admin   = $current_admin ? (bool)$current_admin['is_super_admin'] : false;
} catch (\PDOException $e) {
    $current_admin_id = 0;
    $is_super_admin   = true;
}

$my_permissions = [];
if (!$is_super_admin && $current_admin_id) {
    try {
        $p_stmt = $pdo->prepare("SELECT tab, can_access FROM admin_permissions WHERE admin_id = ?");
        $p_stmt->execute([$current_admin_id]);
        while ($row = $p_stmt->fetch()) {
            $my_permissions[$row['tab']] = (bool)$row['can_access'];
        }
    } catch (\PDOException $e) {}
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
if (!in_array($type, ['web', 'seo', 'smm', 'automation']) || !canAccess($type, $is_super_admin, $my_permissions)) {
    die('Access denied');
}

$sort          = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'asc' : 'desc';
$filter_month  = isset($_GET['filter_month']) ? trim($_GET['filter_month'])  : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
$from_date     = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date       = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';

$filename = "{$type}_leads_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$output = fopen('php://output', 'w');

$table = $type . '_leads';

if ($type === 'web') {
    fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Service', 'Message', 'Status', 'Date']);
    $keys = ['id', 'name', 'email', 'phone', 'service', 'message', 'latest_status', 'created_at'];
} elseif ($type === 'seo') {
    fputcsv($output, ['ID', 'Name', 'Business Name', 'Website', 'Email', 'Phone', 'SEO Need', 'Status', 'Date']);
    $keys = ['id', 'name', 'business_name', 'website', 'email', 'phone', 'seo_need', 'latest_status', 'created_at'];
} elseif ($type === 'smm') {
    fputcsv($output, ['ID', 'Name', 'Business Name', 'Instagram/Website', 'Email', 'Phone', 'SMM Need', 'Status', 'Date']);
    $keys = ['id', 'name', 'business_name', 'instagram_or_website', 'email', 'phone', 'smm_need', 'latest_status', 'created_at'];
} else {
    fputcsv($output, ['ID', 'Name', 'Business Name', 'Email', 'Phone', 'Business Type', 'Message', 'Status', 'Date']);
    $keys = ['id', 'name', 'business_name', 'email', 'phone', 'business_type', 'message', 'latest_status', 'created_at'];
}

$leads = getLeads($pdo, $table, $filter_month, $filter_status, $sort, $from_date, $to_date);

foreach ($leads as $lead) {
    $row = [];
    foreach ($keys as $k) {
        $val = $lead[$k] ?? '';
        if ($k === 'phone' && $val) $val = '="' . $val . '"';
        if ($k === 'created_at' && $val) $val = date('d-m-y', strtotime($val));
        if ($k === 'latest_status' && !$val) $val = 'Untouched';
        $row[] = $val;
    }
    fputcsv($output, $row);
}

fclose($output);
exit;
