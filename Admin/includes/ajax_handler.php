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
            $pdo->prepare("INSERT IGNORE INTO admin_read_leads (admin_id, lead_type, lead_id) VALUES (?, ?, ?)")
                ->execute([$current_admin_id, $type, $id]);
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

        // Check if current status is already Closed - Won or Closed - Lost
        $chk_status = $pdo->prepare("
            SELECT status FROM lead_status_updates 
            WHERE lead_type = ? AND lead_id = ? 
            ORDER BY updated_at DESC LIMIT 1
        ");
        $chk_status->execute([$lead_type, $lead_id]);
        $current_latest = $chk_status->fetchColumn();
        if ($current_latest === 'Closed - Won' || $current_latest === 'Closed - Lost') {
            echo json_encode(['success' => false, 'error' => 'This lead is Closed and cannot be modified again.']); exit;
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

if ($action === 'get_latest_data') {
    require_once __DIR__ . '/functions.php';
    
    // Fetch permissions for current admin
    $my_permissions = [];
    if (!$is_super_admin && $current_admin_id) {
        try {
            $p_stmt = $pdo->prepare("SELECT tab, can_access FROM admin_permissions WHERE admin_id = ?");
            $p_stmt->execute([$current_admin_id]);
            while ($row = $p_stmt->fetch()) {
                if ((bool)$row['can_access']) {
                    $my_permissions[] = $row['tab'];
                }
            }
        } catch (\PDOException $e) {}
    }
    
    // Calculate unread counts
    $unreads = [];
    $tabs = ['web', 'seo', 'smm', 'automation'];
    foreach ($tabs as $t) {
        $unreads[$t] = 0;
        if ($is_super_admin || in_array($t, $my_permissions)) {
            $table = "{$t}_leads";
            $unreads[$t] = getUnreadLeadsCount($pdo, $table, $current_admin_id);
        }
    }
    
    $response = [
        'success' => true,
        'unreads' => $unreads
    ];
    
    $active_tab = isset($_POST['active_tab']) ? $_POST['active_tab'] : '';
    if ($active_tab === 'dashboard') {
        $filter_tab   = isset($_POST['filter_tab']) ? $_POST['filter_tab'] : '';
        $filter_month = isset($_POST['filter_month']) ? $_POST['filter_month'] : '';
        
        $kpis = getDashboardKPIs($pdo, $my_permissions, $is_super_admin, $filter_tab, $filter_month);
        $response['dashboard_kpis'] = $kpis;
    }
    if (in_array($active_tab, ['web', 'seo', 'smm', 'automation'])) {
        $filter_month  = isset($_POST['filter_month']) ? $_POST['filter_month'] : '';
        $filter_status = isset($_POST['filter_status']) ? $_POST['filter_status'] : '';
        $sort          = isset($_POST['sort']) && strtolower($_POST['sort']) === 'asc' ? 'asc' : 'desc';
        $from_date     = isset($_POST['from_date']) ? $_POST['from_date'] : '';
        $to_date       = isset($_POST['to_date']) ? $_POST['to_date'] : '';
        
        $table_name = "{$active_tab}_leads";
        $leads = getLeads($pdo, $table_name, $filter_month, $filter_status, $sort, $from_date, $to_date, $current_admin_id);
        
        $total_leads = count($leads);
        $total_won = 0;
        $total_lost = 0;
        foreach ($leads as $l) {
            if ($l['latest_status'] === 'Closed - Won') $total_won++;
            elseif ($l['latest_status'] === 'Closed - Lost') $total_lost++;
        }
        $total_pending = $total_leads - ($total_won + $total_lost);
        
        $response['metrics'] = [
            'total' => $total_leads,
            'won' => $total_won,
            'pending' => $total_pending
        ];
        
        // Generate table HTML
        $table_html = '';
        if (empty($leads)) {
            $table_html = '<tr><td colspan="5" class="no-leads"><i class="fa-regular fa-folder-open"></i> No ' . $active_tab . ' leads captured yet.</td></tr>';
        } else {
            $map_status_classes = [
                'Qualified' => 'status-qualified',
                'Initial Contact Made' => 'status-contacted',
                'Proposal Sent' => 'status-proposal',
                'In Discussion' => 'status-discussion',
                'Follow-Up Scheduled' => 'status-followup',
                'No Response' => 'status-noresponse',
                'Closed - Won' => 'status-won',
                'Closed - Lost' => 'status-lost'
            ];
            
            $display_names = [
                'web' => 'Web Design',
                'seo' => 'SEO Audit',
                'smm' => 'SMM Audit',
                'automation' => 'WhatsApp Automation'
            ];
            
            foreach ($leads as $lead) {
                $lead_js = array_merge($lead, ['lead_type' => $active_tab, 'display_type' => $display_names[$active_tab]]);
                $status_label = $lead['latest_status'] ?: 'Untouched';
                $status_class = $map_status_classes[$status_label] ?? 'status-noresponse';
                if ($status_label === 'Untouched') $status_class = 'status-noresponse';
                
                $unread_class = !$lead['is_read'] ? 'unread-row' : '';
                $unread_dot = !$lead['is_read'] ? '<span class="unread-dot"></span>' : '';
                
                $lead_json = htmlspecialchars(json_encode($lead_js, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT), ENT_HTML5, 'UTF-8');
                
                $table_html .= '<tr class="lead-row ' . $unread_class . '" ';
                $table_html .= 'data-id="' . $lead['id'] . '" ';
                $table_html .= 'data-type="' . $active_tab . '" ';
                $table_html .= 'data-lead=\'' . $lead_json . '\' ';
                $table_html .= 'onclick="rowClick(this,event)" ';
                $table_html .= 'oncontextmenu="rowContext(this,event)" ';
                $table_html .= 'style="cursor: pointer;">';
                
                $table_html .= '<td style="font-weight: 600;">' . $unread_dot . htmlspecialchars($lead['name']) . '</td>';
                $table_html .= '<td><a href="mailto:' . htmlspecialchars($lead['email']) . '" onclick="event.stopPropagation()" style="color: inherit; text-decoration: none;">' . htmlspecialchars($lead['email']) . '</a></td>';
                $table_html .= '<td><a href="tel:' . htmlspecialchars($lead['phone']) . '" onclick="event.stopPropagation()" style="color: inherit; text-decoration: none;">' . htmlspecialchars($lead['phone']) . '</a></td>';
                $table_html .= '<td>' . htmlspecialchars($lead_js['display_type']) . '</td>';
                $table_html .= '<td><span class="status-badge ' . $status_class . '">' . htmlspecialchars($status_label) . '</span></td>';
                
                $table_html .= '</tr>';
            }
        }
        
        $response['table_html'] = $table_html;
    }
    
    echo json_encode($response);
    exit;
}
