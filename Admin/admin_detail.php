<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/includes/functions.php';

// Get current admin
try {
    $ca_stmt = $pdo->prepare("SELECT id, username, is_super_admin, display_name FROM admin_users WHERE username = ?");
    $ca_stmt->execute([$_SESSION['admin_username']]);
    $current_admin    = $ca_stmt->fetch();
    $current_admin_id = $current_admin ? (int)$current_admin['id'] : 0;
    $is_super_admin   = $current_admin ? (bool)$current_admin['is_super_admin'] : false;
} catch (\PDOException $e) {
    $current_admin_id = 0; $is_super_admin = true;
}

// Only super admin can access this page
if (!$is_super_admin) {
    header('Location: index.php');
    exit;
}

$target_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($target_id <= 0) {
    header('Location: index.php?tab=admins');
    exit;
}

// Fetch target admin
try {
    $t_stmt = $pdo->prepare("SELECT id, username, display_name, email, is_super_admin, created_at, created_by FROM admin_users WHERE id = ?");
    $t_stmt->execute([$target_id]);
    $target_admin = $t_stmt->fetch();
} catch (\PDOException $e) { $target_admin = null; }

if (!$target_admin) {
    header('Location: index.php?tab=admins');
    exit;
}
if ($target_admin['is_super_admin']) {
    header('Location: index.php?tab=admins');
    exit;
}

$from = isset($_GET['from']) ? $_GET['from'] : 'admins';
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';

// Fetch current permissions
$permissions = [];
try {
    $p_stmt = $pdo->prepare("SELECT tab, can_access FROM admin_permissions WHERE admin_id = ?");
    $p_stmt->execute([$target_id]);
    while ($row = $p_stmt->fetch()) { $permissions[$row['tab']] = (bool)$row['can_access']; }
} catch (\PDOException $e) {}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Update permissions
    if ($_POST['action'] === 'update_permissions') {
        $tabs  = ['web','seo','smm','automation','security','analytics'];
        $perms = isset($_POST['permissions']) ? (array)$_POST['permissions'] : [];
        try {
            $ups = $pdo->prepare("INSERT INTO admin_permissions (admin_id, tab, can_access) VALUES (?,?,?) ON DUPLICATE KEY UPDATE can_access = VALUES(can_access)");
            foreach ($tabs as $tab) { $ups->execute([$target_id, $tab, in_array($tab, $perms) ? 1 : 0]); }
            echo json_encode(['success' => true, 'message' => 'Permissions updated successfully.']);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // Delete admin
    if ($_POST['action'] === 'delete_admin') {
        if ($target_admin['is_super_admin']) { echo json_encode(['success' => false, 'error' => 'Cannot delete a Super Admin.']); exit; }
        try {
            $pdo->prepare("DELETE FROM admin_users WHERE id = ? AND is_super_admin = 0")->execute([$target_id]);
            echo json_encode(['success' => true, 'message' => 'Admin account deleted.']);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action.']); exit;
}

$dname    = $target_admin['username'];
$initials = strtoupper(substr($dname, 0, 2));

$tab_defs = [
    'web'        => ['label' => 'Web Leads',         'icon' => 'fa-solid fa-code',          'desc' => 'Can view and export Web Development leads'],
    'seo'        => ['label' => 'SEO Leads',          'icon' => 'fa-solid fa-magnifying-glass','desc' => 'Can view and export SEO leads'],
    'smm'        => ['label' => 'SMM Leads',          'icon' => 'fa-solid fa-share-nodes',    'desc' => 'Can view and export SMM leads'],
    'automation' => ['label' => 'Automation Leads',   'icon' => 'fa-brands fa-whatsapp',      'desc' => 'Can view and export Automation leads'],
    'security'   => ['label' => 'Security',           'icon' => 'fa-solid fa-shield-halved',  'desc' => 'Can change their own password'],
    'analytics'  => ['label' => 'Leaderboard',        'icon' => 'fa-solid fa-ranking-star',   'desc' => 'Can view the admin performance ranking leaderboard'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($dname); ?> — Admin Detail · Adver Digify</title>
    <link rel="icon" type="image/png" href="./assets/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/style.css?v=15">
    <style>
        .save-bar {
            position: fixed; bottom: 0; left: 260px; right: 0;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(8px);
            border-top: 1px solid var(--border-color);
            padding: 16px 40px;
            display: flex; align-items: center; justify-content: space-between;
            z-index: 200; gap: 16px;
            transition: transform 0.3s ease;
            transform: translateY(100%);
        }
        .save-bar.visible { transform: translateY(0); }
        .save-bar-msg { font-size: 13px; color: var(--text-muted); }
        @media (max-width: 768px) { .save-bar { left: 0; padding: 14px 20px; } }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand mobile-hide" style="margin-bottom: 10px;"><img src="assets/ad_logo.png" alt="Adver Digify Logo"></div>
    <hr class="sidebar-divider mobile-hide" style="margin: 15px 16px;">
    <div style="padding: 0 16px 14px;">
        <div style="font-size: 11px; color: var(--sidebar-text); margin-bottom: 4px;">@<?php echo htmlspecialchars($current_admin['username']); ?></div>
        <span class="super-admin-badge"><i class="fa-solid fa-crown"></i> Super Admin</span>
    </div>
    <ul class="sidebar-menu">
        <li class="menu-item"><a href="index.php?tab=dashboard"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li class="menu-item"><a href="index.php?tab=web"><i class="fa-solid fa-code"></i><span>Web Leads</span></a></li>
        <li class="menu-item"><a href="index.php?tab=seo"><i class="fa-solid fa-magnifying-glass"></i><span>SEO Leads</span></a></li>
        <li class="menu-item"><a href="index.php?tab=smm"><i class="fa-solid fa-share-nodes"></i><span>SMM Leads</span></a></li>
        <li class="menu-item"><a href="index.php?tab=automation"><i class="fa-brands fa-whatsapp"></i><span>Automation Leads</span></a></li>
        <li class="menu-item"><a href="index.php?tab=security"><i class="fa-solid fa-shield-halved"></i><span>Security</span></a></li>
        <li class="menu-item active"><a href="index.php?tab=admins"><i class="fa-solid fa-users-gear"></i><span>Admin Management</span></a></li>
    </ul>
    <div class="sidebar-footer"><a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i><span>Log Out</span></a></div>
</div>

<!-- Main Content -->
<div class="main-content" style="padding-bottom: 100px;">

    <!-- Breadcrumb -->
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 28px; font-size: 13px; color: var(--text-muted);">
        <?php if ($from === 'leaderboard'): ?>
            <a href="analytics.php" style="color: var(--primary); text-decoration: none; font-weight: 600;"><i class="fa-solid fa-arrow-left" style="margin-right: 5px;"></i> Leaderboard</a>
        <?php else: ?>
            <a href="index.php?tab=admins" style="color: var(--primary); text-decoration: none; font-weight: 600;"><i class="fa-solid fa-arrow-left" style="margin-right: 5px;"></i> Admin Management</a>
        <?php endif; ?>
        <span style="color: var(--text-light);">/</span>
        <span><?php echo htmlspecialchars($dname); ?></span>
    </div>

    <!-- Toast -->
    <div class="toast-msg" id="toast" style="display:none;">
        <span id="toastMsg"></span>
        <button onclick="document.getElementById('toast').style.display='none'">&times;</button>
    </div>

    <!-- Profile Card -->
    <div class="admin-detail-card">
        <div class="admin-detail-profile">
            <div class="admin-detail-avatar"><?php echo $initials; ?></div>
            <div style="flex:1;">
                <div class="admin-detail-name">@<?php echo htmlspecialchars($target_admin['username']); ?></div>
                <?php if ($target_admin['email']): ?>
                <div class="admin-detail-meta" style="margin-top: 5px;"><i class="fa-regular fa-envelope" style="margin-right:5px;"></i><?php echo htmlspecialchars($target_admin['email']); ?></div>
                <?php endif; ?>
                <div class="admin-detail-meta" style="margin-top:5px;"><i class="fa-regular fa-calendar" style="margin-right:5px;"></i>Account created <?php echo date('F d, Y \a\t h:i A', strtotime($target_admin['created_at'])); ?></div>
            </div>
            <button class="btn btn-danger" id="deleteAdminBtn" onclick="confirmDeleteAdmin()" style="padding: 10px 20px; border-radius: 12px; font-size: 13px; align-self: flex-start;">
                <i class="fa-regular fa-trash-can"></i> Delete Account
            </button>
        </div>

        <hr style="border: none; border-top: 1px solid var(--border-color); margin: 0 0 24px 0;">

        <!-- Permissions -->
        <div class="collapsible-section">
            <div class="collapsible-header" onclick="togglePermissions()" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer; padding: 4px 0 16px 0;">
                <div>
                    <div style="font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 4px;"><i class="fa-solid fa-shield-halved" style="color:var(--primary); margin-right:8px;"></i>View Permissions</div>
                    <div style="font-size: 13px; color: var(--text-muted);">Toggle which sections of the admin panel this user can access.</div>
                </div>
                <i class="fa-solid fa-chevron-down" id="permToggleIcon" style="color: var(--text-light); transition: transform 0.3s;"></i>
            </div>

            <div id="permissionsContent" style="display: none; padding-top: 16px; border-top: 1px solid var(--border-color);">
                <div class="permissions-edit-grid">
                    <?php foreach ($tab_defs as $tab_key => $tab_info): $is_on = isset($permissions[$tab_key]) ? $permissions[$tab_key] : false; ?>
                    <div class="perm-edit-item">
                        <div class="perm-edit-label">
                            <i class="<?php echo $tab_info['icon']; ?>" style="color: var(--primary); width:20px;"></i>
                            <div>
                                <div class="perm-edit-name"><?php echo $tab_info['label']; ?></div>
                                <div class="perm-edit-desc"><?php echo $tab_info['desc']; ?></div>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" class="perm-toggle" data-tab="<?php echo $tab_key; ?>" <?php echo $is_on ? 'checked' : ''; ?> onchange="onPermissionChange()">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Month Filter -->
    <div style="margin-top: 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-chart-line" style="color:var(--primary);"></i> Performance Analytics
        </h3>
        <div style="display: flex; align-items: center; gap: 8px;">
            <label for="filter_month" style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 4px;"><i class="fa-regular fa-calendar-days"></i> Month:</label>
            <input type="month" name="filter_month" id="filter_month" class="filter-control" style="font-size: 12px; padding: 4px 8px; max-width: 120px;" value="<?php echo htmlspecialchars($filter_month); ?>" onchange="filterPerformance()">
            <button type="button" id="reset_btn" class="btn btn-outline btn-sm" style="display: <?php echo $filter_month !== '' ? 'inline-block' : 'none'; ?>; padding: 4px 8px; font-size: 11px; border-radius: 6px;" onclick="resetPerformanceFilter()"><i class="fa-solid fa-rotate-left"></i> Reset</button>
        </div>
    </div>

    <!-- Performance Analytics Card -->
    <div class="admin-detail-card" style="margin-top: 16px;">
        <?php 
        $perf = getSpecificAdminPerformance($pdo, $target_id, $filter_month);
        ?>
        
        <div class="metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div class="metric-card" style="border: 1px solid var(--border-color); box-shadow: none; padding: 16px;">
                <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Leads Handled</div>
                <div style="font-size: 20px; font-weight: 700; color: #111827;" id="perf_handled"><?php echo $perf['handled_count']; ?></div>
            </div>
            <div class="metric-card" style="border: 1px solid var(--border-color); box-shadow: none; padding: 16px;">
                <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Deals Won</div>
                <div style="font-size: 20px; font-weight: 700; color: #166534;" id="perf_won"><?php echo $perf['won_count']; ?></div>
            </div>
            <div class="metric-card" style="border: 1px solid var(--border-color); box-shadow: none; padding: 16px;">
                <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Conversion Rate</div>
                <div style="font-size: 20px; font-weight: 700; color: var(--primary);" id="perf_rate"><?php echo $perf['conversion_rate']; ?>%</div>
            </div>
            <div class="metric-card" style="border: 1px solid var(--border-color); box-shadow: none; padding: 16px;">
                <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Avg Response</div>
                <div style="font-size: 20px; font-weight: 700; color: #111827;" id="perf_response"><?php echo formatResponseTime($perf['avg_response_minutes']); ?></div>
            </div>
            <div class="metric-card" style="border: 1px solid var(--border-color); box-shadow: none; padding: 16px;">
                <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Avg Close Time</div>
                <div style="font-size: 20px; font-weight: 700; color: #111827;" id="perf_close"><?php echo formatResponseTime($perf['avg_conversion_minutes']); ?></div>
            </div>
        </div>
        
        <!-- Category breakdown -->
        <div style="font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 12px; margin-top: 24px;">Category Performance Breakdown</div>
        <div class="table-responsive" style="border: 1px solid var(--border-color); border-radius: 12px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                <thead>
                    <tr style="background: #fbfbfa; border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; color: var(--text-muted); font-size:11px; font-weight: 700;">Service Category</th>
                        <th style="padding: 12px 16px; color: var(--text-muted); font-size:11px; font-weight: 700; text-align: center;">Leads Handled</th>
                        <th style="padding: 12px 16px; color: var(--text-muted); font-size:11px; font-weight: 700; text-align: center;">Deals Won</th>
                        <th style="padding: 12px 16px; color: var(--text-muted); font-size:11px; font-weight: 700; text-align: center;">Conversion Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $cats = ['web' => 'Web Leads', 'seo' => 'SEO Leads', 'smm' => 'SMM Leads', 'automation' => 'Automation Leads'];
                    foreach ($cats as $cat_key => $cat_name): 
                        // Fetch stats specifically for this category
                        $cat_handled = 0;
                        $cat_won = 0;
                        try {
                            $month_q = '';
                            $params_q = [$target_id, $cat_key];
                            if (!empty($filter_month)) {
                                $month_q = " AND al.created_at LIKE ?";
                                $params_q[] = $filter_month . '%';
                            }
                            
                            $lead_table = "{$cat_key}_leads";
                            $sql = "SELECT COUNT(DISTINCT lsu.lead_id) 
                                    FROM lead_status_updates lsu
                                    JOIN `$lead_table` al ON al.id = lsu.lead_id
                                    WHERE lsu.updated_by = ? AND lsu.lead_type = ? $month_q";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params_q);
                            $cat_handled = (int)$stmt->fetchColumn();

                            $cat_won_params = array_merge([$cat_key], $params_q);
                            $sql = "SELECT COUNT(DISTINCT lsu.lead_id) 
                                    FROM lead_status_updates lsu
                                    JOIN (
                                        SELECT lead_id, MAX(id) as max_id
                                        FROM lead_status_updates
                                        WHERE lead_type = ?
                                        GROUP BY lead_id
                                    ) latest ON lsu.id = latest.max_id
                                    JOIN `$lead_table` al ON al.id = lsu.lead_id
                                    WHERE lsu.updated_by = ? AND lsu.lead_type = ? AND lsu.status = 'Closed - Won' $month_q";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($cat_won_params);
                            $cat_won = (int)$stmt->fetchColumn();
                        } catch (\PDOException $e) {}
                        
                        $cat_rate = $cat_handled > 0 ? round(($cat_won / $cat_handled) * 100, 1) : 0;
                    ?>
                        <tr style="border-bottom: 1px solid var(--border-color);" id="cat_row_<?php echo $cat_key; ?>">
                            <td style="padding: 12px 16px; font-weight: 600; color: #111827;"><?php echo $cat_name; ?></td>
                            <td style="padding: 12px 16px; text-align: center; color: #4b5563;"><?php echo $cat_handled; ?></td>
                            <td style="padding: 12px 16px; text-align: center; color: #166534; font-weight: 600;"><?php echo $cat_won; ?></td>
                            <td style="padding: 12px 16px; text-align: center; font-weight: 700; color: #111827;"><?php echo $cat_rate; ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>



<!-- Save bar -->
<div class="save-bar" id="saveBar">
    <span class="save-bar-msg"><i class="fa-solid fa-circle-info" style="margin-right:6px; color:var(--primary);"></i>You have unsaved permission changes.</span>
    <div style="display:flex;gap:12px;">
        <button class="btn btn-outline btn-sm" onclick="discardPermissions()" style="border-radius:10px;">Discard</button>
        <button class="btn btn-primary" id="savePermsBtn" onclick="savePermissions()" style="padding:10px 24px;border-radius:10px;font-size:13px;font-weight:600;">
            <i class="fa-solid fa-floppy-disk"></i> Save Permissions
        </button>
    </div>
</div>

<!-- Delete confirm modal -->
<div class="modal" id="deleteConfirmModal">
    <div class="modal-content" style="max-width:400px;">
        <div class="modal-header" style="background: #fef2f2; border-bottom-color: #fecaca;">
            <div class="modal-title" style="color:#991b1b;"><i class="fa-solid fa-triangle-exclamation"></i> Delete Admin Account</div>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body" style="text-align:center; padding: 30px 24px;">
            <div style="font-size:48px; margin-bottom:16px;">🗑️</div>
            <p style="font-size:15px; font-weight:700; color:#111827; margin-bottom:8px;">Are you absolutely sure?</p>
            <p style="font-size:13px; color:var(--text-muted); margin-bottom:24px;">This will permanently delete <strong><?php echo htmlspecialchars($dname); ?>'s</strong> account and all associated permissions. This action cannot be undone.</p>
            <div style="display:flex; gap:12px; justify-content:center;">
                <button class="btn btn-outline" onclick="closeDeleteModal()" style="padding:10px 22px;border-radius:10px;">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn" onclick="executeDeleteAdmin()" style="padding:10px 22px;border-radius:10px;">
                    <i class="fa-regular fa-trash-can"></i> Yes, Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const ADMIN_DETAIL_ID = <?php echo $target_id; ?>;
let originalPerms = {};
let currentPerms  = {};

// Capture original state
document.querySelectorAll('.perm-toggle').forEach(cb => {
    originalPerms[cb.dataset.tab] = cb.checked;
    currentPerms[cb.dataset.tab]  = cb.checked;
});

function onPermissionChange() {
    document.querySelectorAll('.perm-toggle').forEach(cb => { currentPerms[cb.dataset.tab] = cb.checked; });
    const changed = Object.keys(originalPerms).some(t => originalPerms[t] !== currentPerms[t]);
    document.getElementById('saveBar').classList.toggle('visible', changed);
}

function discardPermissions() {
    document.querySelectorAll('.perm-toggle').forEach(cb => { cb.checked = originalPerms[cb.dataset.tab]; currentPerms[cb.dataset.tab] = originalPerms[cb.dataset.tab]; });
    document.getElementById('saveBar').classList.remove('visible');
}

function savePermissions() {
    const perms = [];
    document.querySelectorAll('.perm-toggle').forEach(cb => { if (cb.checked) perms.push(cb.dataset.tab); });
    const btn = document.getElementById('savePermsBtn');
    btn.disabled = true; btn.innerHTML = '<span class="btn-spinner"></span> Saving...';
    const fd = new FormData();
    fd.append('action', 'update_permissions');
    perms.forEach(p => fd.append('permissions[]', p));
    fetch('admin_detail.php?id=' + ADMIN_DETAIL_ID, { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Permissions';
            if (data.success) {
                // Update original state
                document.querySelectorAll('.perm-toggle').forEach(cb => { originalPerms[cb.dataset.tab] = cb.checked; });
                document.getElementById('saveBar').classList.remove('visible');
                showToast(data.message || 'Permissions saved.');
            } else {
                alert(data.error || 'Failed to save permissions.');
            }
        }).catch(() => { btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-floppy-disk"></i> Save Permissions'; });
}

function confirmDeleteAdmin() { document.getElementById('deleteConfirmModal').classList.add('active'); document.body.style.overflow='hidden'; }
function closeDeleteModal()   { document.getElementById('deleteConfirmModal').classList.remove('active'); document.body.style.overflow=''; }

function executeDeleteAdmin() {
    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true; btn.innerHTML = '<span class="btn-spinner"></span> Deleting...';
    const fd = new FormData(); fd.append('action','delete_admin');
    fetch('admin_detail.php?id=' + ADMIN_DETAIL_ID, { method:'POST', body:fd })
        .then(r => r.json()).then(data => {
            if (data.success) { window.location.href = 'index.php?tab=admins'; }
            else { btn.disabled=false; btn.innerHTML='<i class="fa-regular fa-trash-can"></i> Yes, Delete'; alert(data.error||'Failed to delete.'); }
        }).catch(() => { btn.disabled=false; btn.innerHTML='<i class="fa-regular fa-trash-can"></i> Yes, Delete'; });
}

function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toastMsg').innerHTML = '<i class="fa-solid fa-circle-check" style="margin-right:8px;color:#059669;"></i>' + msg;
    t.style.display = 'flex';
    setTimeout(() => { t.style.display = 'none'; }, 4000);
}

function togglePermissions() {
    const content = document.getElementById('permissionsContent');
    const icon = document.getElementById('permToggleIcon');
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

window.addEventListener('keydown', e => { if (e.key==='Escape') closeDeleteModal(); });
window.addEventListener('click', e => { if (e.target===document.getElementById('deleteConfirmModal')) closeDeleteModal(); });

function filterPerformance() {
    const filterMonth = document.getElementById('filter_month').value;
    const resetBtn = document.getElementById('reset_btn');
    
    if (filterMonth !== '') {
        resetBtn.style.display = 'inline-block';
    } else {
        resetBtn.style.display = 'none';
    }
    
    const fd = new FormData();
    fd.append('action', 'get_admin_performance');
    fd.append('admin_id', ADMIN_DETAIL_ID);
    fd.append('filter_month', filterMonth);
    
    fetch('includes/ajax_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update metrics
                document.getElementById('perf_handled').textContent = data.metrics.handled_count;
                document.getElementById('perf_won').textContent = data.metrics.won_count;
                document.getElementById('perf_rate').textContent = data.metrics.conversion_rate + '%';
                document.getElementById('perf_response').textContent = data.metrics.avg_response_formatted;
                document.getElementById('perf_close').textContent = data.metrics.avg_conversion_formatted;
                
                // Update categories table rows
                data.categories.forEach(cat => {
                    const row = document.getElementById('cat_row_' + cat.key);
                    if (row) {
                        const tds = row.getElementsByTagName('td');
                        if (tds.length >= 4) {
                            tds[1].textContent = cat.handled;
                            tds[2].textContent = cat.won;
                            tds[3].textContent = cat.rate + '%';
                        }
                    }
                });
            }
        })
        .catch(e => console.error("Error filtering performance: ", e));
}

function resetPerformanceFilter() {
    document.getElementById('filter_month').value = '';
    filterPerformance();
}
</script>

<?php
// Helper function for status badge class (server-side, for assigned leads table)
function getStatusBadgeClass($status) {
    $map = [
        'Qualified'            => 'status-qualified',
        'Initial Contact Made' => 'status-contacted',
        'Proposal Sent'        => 'status-proposal',
        'In Discussion'        => 'status-discussion',
        'Follow-Up Scheduled'  => 'status-followup',
        'No Response'          => 'status-noresponse',
        'Closed - Won'         => 'status-won',
        'Closed - Lost'        => 'status-lost',
    ];
    return $map[$status] ?? 'status-noresponse';
}

require_once __DIR__ . '/includes/footer.php';
?>
