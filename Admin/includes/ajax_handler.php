<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

require_once __DIR__ . '/../../db_connect.php';

// Get current admin record
try {
    $ca_stmt = $pdo->prepare("SELECT id, username, is_super_admin, display_name FROM admin_users WHERE username = ?");
    $ca_stmt->execute([$_SESSION['admin_username']]);
    $current_admin = $ca_stmt->fetch();
    $current_admin_id  = $current_admin ? (int)$current_admin['id'] : 0;
    $is_super_admin    = $current_admin ? (bool)$current_admin['is_super_admin'] : false;
} catch (\PDOException $e) {
    $current_admin_id = 0;
    $is_super_admin   = true;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    exit;
}

header('Content-Type: application/json');
$action = $_POST['action'];

if ($action === 'mark_as_read') {
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    $id   = isset($_POST['id'])   ? intval($_POST['id']) : 0;
    if ($id > 0 && in_array($type, ['web', 'seo', 'smm', 'automation'])) {
        try {
            $table = "{$type}_leads";
            $pdo->prepare("UPDATE `$table` SET is_read = 1 WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
    }
    exit;
}

if ($action === 'add_admin') {
    if (!$is_super_admin) { echo json_encode(['success' => false, 'error' => 'Unauthorized.']); exit; }

    $username     = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';
    $email        = trim($_POST['email'] ?? '');
    $perms        = isset($_POST['permissions']) ? (array)$_POST['permissions'] : [];

    if (empty($username) || empty($password) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Username, email and password are required.']); exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters.']); exit;
    }

    try {
        $chk = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
        $chk->execute([$username, $email]);
        if ($chk->fetch()) { echo json_encode(['success' => false, 'error' => 'Username or Email already exists.']); exit; }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ins  = $pdo->prepare("INSERT INTO admin_users (username, password, is_super_admin, display_name, email, created_by) VALUES (?, ?, 0, ?, ?, ?)");
        $ins->execute([$username, $hash, '', $email, $current_admin_id]);
        $new_id = (int)$pdo->lastInsertId();

        $all_tabs = ['web', 'seo', 'smm', 'automation', 'security'];
        $perm_ins = $pdo->prepare("INSERT INTO admin_permissions (admin_id, tab, can_access) VALUES (?, ?, ?)");
        foreach ($all_tabs as $tab) {
            $perm_ins->execute([$new_id, $tab, in_array($tab, $perms) ? 1 : 0]);
        }
        echo json_encode(['success' => true, 'admin_id' => $new_id, 'message' => 'Admin created successfully.']);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_admin') {
    if (!$is_super_admin) { echo json_encode(['success' => false, 'error' => 'Unauthorized.']); exit; }
    $target_id = intval($_POST['admin_id'] ?? 0);
    if ($target_id <= 0 || $target_id === $current_admin_id) {
        echo json_encode(['success' => false, 'error' => 'Cannot delete yourself.']); exit;
    }
    try {
        $chk = $pdo->prepare("SELECT is_super_admin FROM admin_users WHERE id = ?");
        $chk->execute([$target_id]);
        $target = $chk->fetch();
        if (!$target) { echo json_encode(['success' => false, 'error' => 'Admin not found.']); exit; }
        if ($target['is_super_admin']) { echo json_encode(['success' => false, 'error' => 'Cannot delete a Super Admin.']); exit; }
        $pdo->prepare("DELETE FROM admin_users WHERE id = ?")->execute([$target_id]);
        echo json_encode(['success' => true, 'message' => 'Admin deleted.']);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'update_permissions') {
    if (!$is_super_admin) { echo json_encode(['success' => false, 'error' => 'Unauthorized.']); exit; }
    $target_id = intval($_POST['admin_id'] ?? 0);
    $perms     = isset($_POST['permissions']) ? (array)$_POST['permissions'] : [];
    $all_tabs  = ['web', 'seo', 'smm', 'automation', 'security'];
    try {
        $ups = $pdo->prepare("INSERT INTO admin_permissions (admin_id, tab, can_access) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE can_access = VALUES(can_access)");
        foreach ($all_tabs as $tab) {
            $ups->execute([$target_id, $tab, in_array($tab, $perms) ? 1 : 0]);
        }
        echo json_encode(['success' => true, 'message' => 'Permissions updated.']);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}



if ($action === 'update_lead_status') {
    if ($is_super_admin) {
        echo json_encode(['success' => false, 'error' => 'Super Admins cannot update lead pipeline statuses directly.']); exit;
    }
    $lead_type   = $_POST['lead_type'] ?? '';
    $lead_id     = intval($_POST['lead_id'] ?? 0);
    $status      = trim($_POST['status'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $valid_statuses = ['Qualified','Initial Contact Made','Proposal Sent','In Discussion','Follow-Up Scheduled','No Response','Closed - Won','Closed - Lost'];
    if (!in_array($status, $valid_statuses) || !in_array($lead_type, ['web','seo','smm','automation']) || $lead_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters.']); exit;
    }
    try {
        // Verify the sub-admin has access to this tab
        $chk = $pdo->prepare("SELECT can_access FROM admin_permissions WHERE admin_id = ? AND tab = ?");
        $chk->execute([$current_admin_id, $lead_type]);
        $perm = $chk->fetch();
        if (!$perm || !$perm['can_access']) {
            echo json_encode(['success' => false, 'error' => 'You do not have access to this lead tab.']); exit;
        }

        $ins = $pdo->prepare("INSERT INTO lead_status_updates (lead_type, lead_id, updated_by, status, description) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$lead_type, $lead_id, $current_admin_id, $status, $description ?: null]);
        echo json_encode(['success' => true, 'message' => 'Pipeline stage updated.']);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_lead_timeline') {
    $lead_type = $_POST['lead_type'] ?? '';
    $lead_id   = intval($_POST['lead_id'] ?? 0);
    if (!in_array($lead_type, ['web','seo','smm','automation']) || $lead_id <= 0) {
        echo json_encode(['success' => false, 'timeline' => []]); exit;
    }
    try {
        // Fetch timeline
        $t_stmt = $pdo->prepare(
            "SELECT lsu.*, COALESCE(au.display_name, au.username) AS updated_by_name
             FROM lead_status_updates lsu
             LEFT JOIN admin_users au ON au.id = lsu.updated_by
             WHERE lsu.lead_type = ? AND lsu.lead_id = ?
             ORDER BY lsu.updated_at ASC"
        );
        $t_stmt->execute([$lead_type, $lead_id]);
        $timeline = $t_stmt->fetchAll();

        // Find tab owners (Admins with access to this tab)
        $tab_owners = [];
        $o_stmt = $pdo->prepare(
            "SELECT COALESCE(au.display_name, au.username) AS name 
             FROM admin_permissions ap
             JOIN admin_users au ON au.id = ap.admin_id
             WHERE ap.tab = ? AND ap.can_access = 1 AND au.is_super_admin = 0"
        );
        $o_stmt->execute([$lead_type]);
        while ($row = $o_stmt->fetch()) {
            $tab_owners[] = $row['name'];
        }

        echo json_encode([
            'success'      => true,
            'tab_owners'   => $tab_owners,
            'timeline'     => $timeline,
            'is_super'     => $is_super_admin,
        ]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'timeline' => []]);
    }
    exit;
}
