<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../db_connect.php';

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
        $tabs  = ['web','seo','smm','automation','security'];
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
    <link rel="stylesheet" href="./assets/style.css?v=7">
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
        <a href="index.php?tab=admins" style="color: var(--primary); text-decoration: none; font-weight: 600;"><i class="fa-solid fa-arrow-left" style="margin-right: 5px;"></i> Admin Management</a>
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
