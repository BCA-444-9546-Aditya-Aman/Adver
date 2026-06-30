<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/functions.php';

try {
    $ca_stmt = $pdo->prepare("SELECT id, username, is_super_admin FROM admin_users WHERE username = ?");
    $ca_stmt->execute([$_SESSION['admin_username']]);
    $current_admin = $ca_stmt->fetch();
    $current_admin_id  = $current_admin ? (int)$current_admin['id'] : 0;
    $is_super_admin    = $current_admin ? (bool)$current_admin['is_super_admin'] : false;
    $current_display   = $_SESSION['admin_username'];
} catch (\PDOException $e) {
    $current_admin_id = 0;
    $is_super_admin   = true;
    $current_display  = $_SESSION['admin_username'];
}

// Sub-admin permission map
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

// Ensure active tab is defined by the parent page
if (!isset($active_tab)) {
    $active_tab = 'dashboard';
}

// Check permission for current page (if not dashboard or assignments or admins)
if ($active_tab !== 'dashboard' && $active_tab !== 'assignments' && $active_tab !== 'admins') {
    if (!canAccess($active_tab, $is_super_admin, $my_permissions)) {
        header('Location: dashboard.php');
        exit;
    }
}
if ($active_tab === 'admins' && !$is_super_admin) {
    header('Location: dashboard.php');
    exit;
}

// Unread counts for sidebar badges
$unread_web        = canAccess('web', $is_super_admin, $my_permissions)        ? getUnreadLeadsCount($pdo, 'web_leads', $current_admin_id)        : 0;
$unread_seo        = canAccess('seo', $is_super_admin, $my_permissions)        ? getUnreadLeadsCount($pdo, 'seo_leads', $current_admin_id)        : 0;
$unread_smm        = canAccess('smm', $is_super_admin, $my_permissions)        ? getUnreadLeadsCount($pdo, 'smm_leads', $current_admin_id)        : 0;
$unread_automation = canAccess('automation', $is_super_admin, $my_permissions) ? getUnreadLeadsCount($pdo, 'automation_leads', $current_admin_id) : 0;

// Fetch all sub-admins for assignment dropdown if super admin
$all_sub_admins = [];
if ($is_super_admin) {
    try {
        $sa_stmt = $pdo->query("SELECT id, username, email FROM admin_users WHERE is_super_admin = 0 ORDER BY username ASC");
        $all_sub_admins = $sa_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($active_tab); ?> - Adver Digify Dashboard</title>
    <link rel="icon" type="image/png" href="./assets/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/style.css?v=13">
    <script>
        const currentActiveTab = "<?php echo $active_tab; ?>";
    </script>
</head>
<body>

<!-- Mobile Navbar -->
<div class="mobile-navbar">
    <div class="mobile-logo"><img src="assets/ad_logo.png" alt="Adver Digify Logo"></div>
    <button class="hamburger-menu" id="hamburgerBtn" aria-label="Toggle Navigation"><i class="fa-solid fa-bars"></i></button>
</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Mobile Sidebar -->
<div class="msb" id="mobileSidebar">
    <nav class="msb-nav">
        <a href="dashboard.php" class="msb-link <?php echo $active_tab === 'dashboard' ? 'msb-active' : ''; ?>"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
        <?php if (canAccess('web', $is_super_admin, $my_permissions)): ?>
        <a href="leads_web.php" class="msb-link <?php echo $active_tab === 'web' ? 'msb-active' : ''; ?>"><i class="fa-solid fa-code"></i><span>Web Leads</span><span class="msb-badge" data-badge-type="web" <?php echo $unread_web > 0 ? '' : 'style="display: none;"'; ?>><?php echo $unread_web; ?></span></a>
        <?php endif; ?>
        <?php if (canAccess('seo', $is_super_admin, $my_permissions)): ?>
        <a href="leads_seo.php" class="msb-link <?php echo $active_tab === 'seo' ? 'msb-active' : ''; ?>"><i class="fa-solid fa-magnifying-glass"></i><span>SEO Leads</span><span class="msb-badge" data-badge-type="seo" <?php echo $unread_seo > 0 ? '' : 'style="display: none;"'; ?>><?php echo $unread_seo; ?></span></a>
        <?php endif; ?>
        <?php if (canAccess('smm', $is_super_admin, $my_permissions)): ?>
        <a href="leads_smm.php" class="msb-link <?php echo $active_tab === 'smm' ? 'msb-active' : ''; ?>"><i class="fa-solid fa-share-nodes"></i><span>SMM Leads</span><span class="msb-badge" data-badge-type="smm" <?php echo $unread_smm > 0 ? '' : 'style="display: none;"'; ?>><?php echo $unread_smm; ?></span></a>
        <?php endif; ?>
        <?php if (canAccess('automation', $is_super_admin, $my_permissions)): ?>
        <a href="leads_automation.php" class="msb-link <?php echo $active_tab === 'automation' ? 'msb-active' : ''; ?>"><i class="fa-brands fa-whatsapp"></i><span>Automation Leads</span><span class="msb-badge" data-badge-type="automation" <?php echo $unread_automation > 0 ? '' : 'style="display: none;"'; ?>><?php echo $unread_automation; ?></span></a>
        <?php endif; ?>
        <?php if (canAccess('security', $is_super_admin, $my_permissions)): ?>
        <a href="security.php" class="msb-link <?php echo $active_tab === 'security' ? 'msb-active' : ''; ?>"><i class="fa-solid fa-shield-halved"></i><span>Security</span></a>
        <?php endif; ?>
        <?php if ($is_super_admin): ?>
        <a href="admins.php" class="msb-link <?php echo $active_tab === 'admins' ? 'msb-active' : ''; ?>"><i class="fa-solid fa-users-gear"></i><span>Admin Management</span></a>
        <?php endif; ?>
    </nav>
    <div class="msb-footer"><a href="logout.php" class="msb-logout"><i class="fa-solid fa-right-from-bracket"></i><span>Log Out</span></a></div>
</div>

<!-- Desktop Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand mobile-hide" style="margin-bottom: 10px;"><img src="assets/ad_logo.png" alt="Adver Digify Logo"></div>
    <hr class="sidebar-divider mobile-hide" style="margin: 15px 16px;">

    <?php if ($is_super_admin): ?>
    <div style="padding: 0 16px 14px;">
        <span class="super-admin-badge"><i class="fa-solid fa-crown"></i> Super Admin</span>
    </div>
    <?php endif; ?>

    <ul class="sidebar-menu">
        <li class="menu-item <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>" data-tab="dashboard">
            <a href="dashboard.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
        </li>
        <?php if (canAccess('web', $is_super_admin, $my_permissions)): ?>
        <li class="menu-item <?php echo $active_tab === 'web' ? 'active' : ''; ?>" data-tab="web">
            <a href="leads_web.php"><i class="fa-solid fa-code"></i><span>Web Leads</span><span class="unread-badge" id="badge-web" <?php echo $unread_web > 0 ? '' : 'style="display: none;"'; ?>><?php echo $unread_web; ?></span></a>
        </li>
        <?php endif; ?>
        <?php if (canAccess('seo', $is_super_admin, $my_permissions)): ?>
        <li class="menu-item <?php echo $active_tab === 'seo' ? 'active' : ''; ?>" data-tab="seo">
            <a href="leads_seo.php"><i class="fa-solid fa-magnifying-glass"></i><span>SEO Leads</span><span class="unread-badge" id="badge-seo" <?php echo $unread_seo > 0 ? '' : 'style="display: none;"'; ?>><?php echo $unread_seo; ?></span></a>
        </li>
        <?php endif; ?>
        <?php if (canAccess('smm', $is_super_admin, $my_permissions)): ?>
        <li class="menu-item <?php echo $active_tab === 'smm' ? 'active' : ''; ?>" data-tab="smm">
            <a href="leads_smm.php"><i class="fa-solid fa-share-nodes"></i><span>SMM Leads</span><span class="unread-badge" id="badge-smm" <?php echo $unread_smm > 0 ? '' : 'style="display: none;"'; ?>><?php echo $unread_smm; ?></span></a>
        </li>
        <?php endif; ?>
        <?php if (canAccess('automation', $is_super_admin, $my_permissions)): ?>
        <li class="menu-item <?php echo $active_tab === 'automation' ? 'active' : ''; ?>" data-tab="automation">
            <a href="leads_automation.php"><i class="fa-brands fa-whatsapp"></i><span>Automation Leads</span><span class="unread-badge" id="badge-automation" <?php echo $unread_automation > 0 ? '' : 'style="display: none;"'; ?>><?php echo $unread_automation; ?></span></a>
        </li>
        <?php endif; ?>
        <?php if (canAccess('security', $is_super_admin, $my_permissions)): ?>
        <li class="menu-item <?php echo $active_tab === 'security' ? 'active' : ''; ?>" data-tab="security">
            <a href="security.php"><i class="fa-solid fa-shield-halved"></i><span>Security</span></a>
        </li>
        <?php endif; ?>
        <?php if ($is_super_admin): ?>
        <li class="menu-item <?php echo $active_tab === 'admins' ? 'active' : ''; ?>" data-tab="admins">
            <a href="admins.php"><i class="fa-solid fa-users-gear"></i><span>Admin Management</span></a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i><span>Log Out</span></a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <?php if (isset($_SESSION['msg'])): ?>
    <div class="toast-msg" id="toast">
        <span><i class="fa-solid fa-circle-check" style="margin-right: 8px;"></i><?php echo $_SESSION['msg']; ?></span>
        <button onclick="document.getElementById('toast').style.display='none'">&times;</button>
    </div>
    <?php unset($_SESSION['msg']); endif; ?>
