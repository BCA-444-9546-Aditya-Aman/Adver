<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../db_connect.php';

// Handle AJAX Request to Mark Lead as Read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_as_read') {
    header('Content-Type: application/json');
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($id > 0 && in_array($type, ['web', 'seo', 'smm', 'automation'])) {
        try {
            $table = "{$type}_leads";
            $stmt = $pdo->prepare("UPDATE `$table` SET is_read = 1 WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            exit;
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
    exit;
}

$password_err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_pass = isset($_POST['cur_pass_val']) ? $_POST['cur_pass_val'] : '';
    $new_pass = isset($_POST['new_pass_val']) ? $_POST['new_pass_val'] : '';
    $confirm_pass = isset($_POST['cfm_pass_val']) ? $_POST['cfm_pass_val'] : '';

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $password_err = "All fields are required.";
    } elseif ($new_pass !== $confirm_pass) {
        $password_err = "New password and confirmation do not match.";
    } elseif (strlen($new_pass) < 6) {
        $password_err = "New password must be at least 6 characters long.";
    } else {
        $username = $_SESSION['admin_username'];
        $stmt = $pdo->prepare("SELECT id, password FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_pass, $user['password'])) {
            $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $update_stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
            $update_stmt->execute([$new_hash, $user['id']]);

            $_SESSION['msg'] = "Password changed successfully.";
            header("Location: index.php?tab=security");
            exit;
        } else {
            $password_err = "Incorrect current password.";
        }
    }
}

// Retrieve GET filters
$sort = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'asc' : 'desc';
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$exact_date = isset($_GET['exact_date']) ? trim($_GET['exact_date']) : '';

// Handle Export CSV action
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    if (in_array($type, ['web', 'seo', 'smm', 'automation'])) {
        $filename = "{$type}_leads_" . date('Y-m-d') . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        if ($type === 'web') {
            fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Service', 'Message', 'Date']);
            $table = 'web_leads';
            $fields = 'id, name, email, phone, service, message, created_at';
        } elseif ($type === 'seo') {
            fputcsv($output, ['ID', 'Name', 'Business Name', 'Website', 'Email', 'Phone', 'SEO Need', 'Date']);
            $table = 'seo_leads';
            $fields = 'id, name, business_name, website, email, phone, seo_need, created_at';
        } elseif ($type === 'smm') {
            fputcsv($output, ['ID', 'Name', 'Business Name', 'Instagram/Website', 'Email', 'Phone', 'SMM Need', 'Date']);
            $table = 'smm_leads';
            $fields = 'id, name, business_name, instagram_or_website, email, phone, smm_need, created_at';
        } else {
            fputcsv($output, ['ID', 'Name', 'Business Name', 'Email', 'Phone', 'Business Type', 'Message', 'Date']);
            $table = 'automation_leads';
            $fields = 'id, name, business_name, email, phone, business_type, message, created_at';
        }

        // Build filtered query for export
        $sql = "SELECT $fields FROM $table WHERE 1=1";
        $params = [];
        if (!empty($exact_date)) {
            $sql .= " AND DATE(created_at) = ?";
            $params[] = $exact_date;
        } else {
            if (!empty($from_date)) {
                $sql .= " AND DATE(created_at) >= ?";
                $params[] = $from_date;
            }
            if (!empty($to_date)) {
                $sql .= " AND DATE(created_at) <= ?";
                $params[] = $to_date;
            }
        }
        $sql .= " ORDER BY created_at " . ($sort === 'asc' ? 'ASC' : 'DESC');

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($row['phone'])) {
                // Force Excel to treat phone numbers as text to preserve leading zeros and prevent scientific notation
                $row['phone'] = '="' . $row['phone'] . '"';
            }
            if (isset($row['created_at'])) {
                // Format date as dd-mm-yy to prevent Excel column width overflow (###)
                $row['created_at'] = date('d-m-y', strtotime($row['created_at']));
            }
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}

// Handle Delete Lead action
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id > 0 && in_array($type, ['web', 'seo', 'smm', 'automation'])) {
        $table = "{$type}_leads";
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['msg'] = ucfirst($type) . " lead deleted successfully.";

        // Re-append active filters to maintain view state after deleting
        $filter_params = "";
        if (!empty($exact_date))
            $filter_params .= "&exact_date=" . urlencode($exact_date);
        if (!empty($from_date))
            $filter_params .= "&from_date=" . urlencode($from_date);
        if (!empty($to_date))
            $filter_params .= "&to_date=" . urlencode($to_date);
        if ($sort !== 'desc')
            $filter_params .= "&sort=" . urlencode($sort);

        header("Location: index.php?tab={$type}" . $filter_params);
        exit;
    }
}

// Helper function to query lead counts with filters
function getLeadsCount($pdo, $table, $from_date, $to_date, $exact_date)
{
    try {
        $sql = "SELECT COUNT(*) FROM $table WHERE 1=1";
        $params = [];
        if (!empty($exact_date)) {
            $sql .= " AND DATE(created_at) = ?";
            $params[] = $exact_date;
        } else {
            if (!empty($from_date)) {
                $sql .= " AND DATE(created_at) >= ?";
                $params[] = $from_date;
            }
            if (!empty($to_date)) {
                $sql .= " AND DATE(created_at) <= ?";
                $params[] = $to_date;
            }
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (\PDOException $e) {
        return 0;
    }
}

// Helper function to query unread leads count
function getUnreadLeadsCount($pdo, $table)
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE is_read = 0");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (\PDOException $e) {
        return 0;
    }
}

// Fetch total metrics using filters
$web_count = getLeadsCount($pdo, 'web_leads', $from_date, $to_date, $exact_date);
$seo_count = getLeadsCount($pdo, 'seo_leads', $from_date, $to_date, $exact_date);
$smm_count = getLeadsCount($pdo, 'smm_leads', $from_date, $to_date, $exact_date);
$automation_count = getLeadsCount($pdo, 'automation_leads', $from_date, $to_date, $exact_date);
$total_count = $web_count + $seo_count + $smm_count + $automation_count;

// Fetch unread counts
$unread_web = getUnreadLeadsCount($pdo, 'web_leads');
$unread_seo = getUnreadLeadsCount($pdo, 'seo_leads');
$unread_smm = getUnreadLeadsCount($pdo, 'smm_leads');
$unread_automation = getUnreadLeadsCount($pdo, 'automation_leads');

// Helper function to query leads with filters and sorting
function getLeads($pdo, $table, $from_date, $to_date, $exact_date, $sort)
{
    try {
        $sql = "SELECT * FROM $table WHERE 1=1";
        $params = [];
        if (!empty($exact_date)) {
            $sql .= " AND DATE(created_at) = ?";
            $params[] = $exact_date;
        } else {
            if (!empty($from_date)) {
                $sql .= " AND DATE(created_at) >= ?";
                $params[] = $from_date;
            }
            if (!empty($to_date)) {
                $sql .= " AND DATE(created_at) <= ?";
                $params[] = $to_date;
            }
        }
        $sql .= " ORDER BY created_at " . ($sort === 'asc' ? 'ASC' : 'DESC');
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        return [];
    }
}

// Fetch all leads for display based on filters
$web_leads = getLeads($pdo, 'web_leads', $from_date, $to_date, $exact_date, $sort);
$seo_leads = getLeads($pdo, 'seo_leads', $from_date, $to_date, $exact_date, $sort);
$smm_leads = getLeads($pdo, 'smm_leads', $from_date, $to_date, $exact_date, $sort);
$automation_leads = getLeads($pdo, 'automation_leads', $from_date, $to_date, $exact_date, $sort);

// Recent leads logic removed

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Helper function to render a unified date filter & sorting bar inside tabs
function renderFilterBar($active_tab, $from_date, $to_date, $exact_date, $sort)
{
    ?>
    <form method="GET" action="" class="filter-bar" style="margin-bottom: 25px; margin-top: 15px;">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">

        <div class="filter-group">
            <label for="exact_date"><i class="fa-regular fa-calendar-check"></i> Specific Date:</label>
            <input type="date" id="exact_date" name="exact_date" class="filter-control"
                value="<?php echo htmlspecialchars($exact_date); ?>" onchange="this.form.submit()">
        </div>

        <div class="filter-group" style="font-size: 11px; color: var(--text-light); font-weight: 700; margin: 0 5px;">OR
        </div>

        <div class="filter-group">
            <label for="from_date"><i class="fa-regular fa-calendar-days"></i> From:</label>
            <input type="date" id="from_date" name="from_date" class="filter-control"
                value="<?php echo htmlspecialchars($from_date); ?>" onchange="this.form.submit()">
        </div>

        <div class="filter-group">
            <label for="to_date"><i class="fa-regular fa-calendar-days"></i> To:</label>
            <input type="date" id="to_date" name="to_date" class="filter-control"
                value="<?php echo htmlspecialchars($to_date); ?>" onchange="this.form.submit()">
        </div>

        <div class="filter-group">
            <label for="sort"><i class="fa-solid fa-arrow-down-z-a"></i> Sort By Date:</label>
            <select id="sort" name="sort" class="filter-control" onchange="this.form.submit()">
                <option value="desc" <?php echo $sort === 'desc' ? 'selected' : ''; ?>>Newest First</option>
                <option value="asc" <?php echo $sort === 'asc' ? 'selected' : ''; ?>>Oldest First</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px; margin-left: auto;">
            <a href="?tab=<?php echo htmlspecialchars($active_tab); ?>" class="btn btn-outline btn-sm"><i
                    class="fa-solid fa-arrow-rotate-left"></i> Reset</a>
        </div>
    </form>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Adver Digify</title>
    <link rel="icon" type="image/png" href="./assets/favicon.png">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
        rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="./assets/style.css?v=4">

</head>

<body>

    <!-- Mobile Navbar -->
    <div class="mobile-navbar">
        <div class="mobile-logo">
            <img src="assets/ad_logo.png" alt="Adver Digify Logo">
        </div>
        <button class="hamburger-menu" id="hamburgerBtn" aria-label="Toggle Navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <!-- Sidebar Overlay Backdrop for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Mobile Sidebar Drawer (separate from desktop sidebar, zero CSS conflicts) -->
    <div class="msb" id="mobileSidebar">
        <nav class="msb-nav">
            <a href="?tab=dashboard" class="msb-link <?php echo $active_tab === 'dashboard' ? 'msb-active' : ''; ?>">
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>
            <a href="?tab=web" class="msb-link <?php echo $active_tab === 'web' ? 'msb-active' : ''; ?>">
                <i class="fa-solid fa-code"></i>
                <span>Web Leads</span>
                <?php if ($unread_web > 0): ?>
                <span class="msb-badge" data-badge-type="web"><?php echo $unread_web; ?></span>
                <?php endif; ?>
            </a>
            <a href="?tab=seo" class="msb-link <?php echo $active_tab === 'seo' ? 'msb-active' : ''; ?>">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>SEO Leads</span>
                <?php if ($unread_seo > 0): ?>
                <span class="msb-badge" data-badge-type="seo"><?php echo $unread_seo; ?></span>
                <?php endif; ?>
            </a>
            <a href="?tab=smm" class="msb-link <?php echo $active_tab === 'smm' ? 'msb-active' : ''; ?>">
                <i class="fa-solid fa-share-nodes"></i>
                <span>SMM Leads</span>
                <?php if ($unread_smm > 0): ?>
                <span class="msb-badge" data-badge-type="smm"><?php echo $unread_smm; ?></span>
                <?php endif; ?>
            </a>
            <a href="?tab=automation" class="msb-link <?php echo $active_tab === 'automation' ? 'msb-active' : ''; ?>">
                <i class="fa-brands fa-whatsapp"></i>
                <span>Automation Leads</span>
                <?php if ($unread_automation > 0): ?>
                <span class="msb-badge" data-badge-type="automation"><?php echo $unread_automation; ?></span>
                <?php endif; ?>
            </a>
            <a href="?tab=security" class="msb-link <?php echo $active_tab === 'security' ? 'msb-active' : ''; ?>">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Security</span>
            </a>
        </nav>
        <div class="msb-footer">
            <a href="logout.php" class="msb-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Log Out</span>
            </a>
        </div>
    </div>


    <div class="sidebar">
        <div class="sidebar-brand mobile-hide" style="margin-bottom: 10px;">
            <img src="assets/ad_logo.png" alt="Adver Digify Logo">
        </div>
        <hr class="sidebar-divider mobile-hide" style="margin: 15px 16px;">

        <ul class="sidebar-menu">
            <li class="menu-item <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>" data-tab="dashboard">
                <a href="?tab=dashboard">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item <?php echo $active_tab === 'web' ? 'active' : ''; ?>" data-tab="web">
                <a href="?tab=web">
                    <i class="fa-solid fa-code"></i>
                    <span>Web Leads</span>
                    <span class="unread-badge" id="badge-web" <?php echo $unread_web > 0 ? '' : 'style="display: none;"'; ?>><?php echo $unread_web; ?></span>
                </a>
            </li>
            <li class="menu-item <?php echo $active_tab === 'seo' ? 'active' : ''; ?>" data-tab="seo">
                <a href="?tab=seo">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>SEO Leads</span>
                    <span class="unread-badge" id="badge-seo" <?php echo $unread_seo > 0 ? '' : 'style="display: none;"'; ?>><?php echo $unread_seo; ?></span>
                </a>
            </li>
            <li class="menu-item <?php echo $active_tab === 'smm' ? 'active' : ''; ?>" data-tab="smm">
                <a href="?tab=smm">
                    <i class="fa-solid fa-share-nodes"></i>
                    <span>SMM Leads</span>
                    <span class="unread-badge" id="badge-smm" <?php echo $unread_smm > 0 ? '' : 'style="display: none;"'; ?>><?php echo $unread_smm; ?></span>
                </a>
            </li>
            <li class="menu-item <?php echo $active_tab === 'automation' ? 'active' : ''; ?>" data-tab="automation">
                <a href="?tab=automation">
                    <i class="fa-brands fa-whatsapp"></i>
                    <span>Automation Leads</span>
                    <span class="unread-badge" id="badge-automation" <?php echo $unread_automation > 0 ? '' : 'style="display: none;"'; ?>><?php echo $unread_automation; ?></span>
                </a>
            </li>
            <li class="menu-item <?php echo $active_tab === 'security' ? 'active' : ''; ?>" data-tab="security">
                <a href="?tab=security">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Security</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Log Out</span>
            </a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">

        <?php if (isset($_SESSION['msg'])): ?>
            <div class="toast-msg" id="toast">
                <span><i class="fa-solid fa-circle-check" style="margin-right: 8px;"></i>
                    <?php echo $_SESSION['msg']; ?></span>
                <button onclick="document.getElementById('toast').style.display='none'">&times;</button>
            </div>
            <?php unset($_SESSION['msg']); ?>
        <?php endif; ?>


        <!-- 1. DASHBOARD TAB -->
        <div class="dashboard-section <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>" id="tab-dashboard">
            <!-- Welcoming Layout Directly on Page (No Outer Card Box) -->
            <div
                style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; gap: 30px; padding: 20px 0; width: 100%;">

                <div class="welcome-illustration"
                    style="margin-bottom: -10px; padding: 0; font-size: 75px; background: linear-gradient(135deg, var(--primary) 0%, var(--success) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; animation: float 6s ease-in-out infinite;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>

                <div style="max-width: 100%; display: flex; flex-direction: column; align-items: center;">
                    <span class="welcome-badge"
                        style="font-size: 11px; padding: 6px 14px; margin-bottom: 20px; color: var(--success); background: var(--success-glow); border: 1px solid rgba(5, 150, 105, 0.15);"><i
                            class="fa-solid fa-rocket"></i> Adver Digify Command Center</span>
                    <h1 class="welcome-text"
                        style="font-size: 42px; margin-bottom: 20px; line-height: 1.2; color: #111827;">Welcome back,
                        <span class="gradient-text"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>!
                    </h1>

                    <div class="system-description"
                        style="max-width: 1000px; margin: 0 auto; display: flex; flex-direction: column; align-items: center;">
                        <p class="welcome-subtext"
                            style="font-size: 16px; margin-bottom: 35px; line-height: 1.8; color: var(--text-muted); text-align: center; max-width: 750px;">
                            This command center provides you with a centralized hub to monitor, review, and manage
                            inbound business inquiries captured from your target marketing landing pages.
                        </p>


                    </div>
                </div>

                <div class="welcome-actions"
                    style="display: flex; gap: 15px; margin-top: 10px; z-index: 2; flex-wrap: wrap; justify-content: center; width: 100%;">
                    <a href="?tab=web" class="btn btn-primary"
                        style="padding: 12px 28px; border-radius: 12px; font-size: 14px; font-weight: 600;"><i
                            class="fa-solid fa-database"></i> Browse Web Leads</a>
                    <a href="?tab=seo" class="btn btn-outline"
                        style="padding: 12px 28px; border-radius: 12px; font-size: 14px; font-weight: 600; color: var(--text-muted); border-color: #d1d5db; background: #ffffff;"><i
                            class="fa-solid fa-magnifying-glass"></i> SEO Leads</a>
                    <a href="?tab=smm" class="btn btn-outline"
                        style="padding: 12px 28px; border-radius: 12px; font-size: 14px; font-weight: 600; color: var(--text-muted); border-color: #d1d5db; background: #ffffff;"><i
                            class="fa-solid fa-share-nodes"></i> SMM Leads</a>
                    <a href="?tab=automation" class="btn btn-outline"
                        style="padding: 12px 28px; border-radius: 12px; font-size: 14px; font-weight: 600; color: var(--text-muted); border-color: #d1d5db; background: #ffffff;"><i
                            class="fa-brands fa-whatsapp"></i> Automation Leads</a>
                </div>
            </div>
        </div>

        <!-- 2. WEB LEADS TAB -->
        <div class="dashboard-section <?php echo $active_tab === 'web' ? 'active' : ''; ?>" id="tab-web">
            <div class="content-header">
                <div class="header-title">
                    <h1>Web Development Leads</h1>
                    <p>Enquiries collected from Web Landing page packages & modal forms.</p>
                </div>
            </div>

            <?php renderFilterBar($active_tab, $from_date, $to_date, $exact_date, $sort); ?>

            <div class="table-card" style="margin-top: 20px;">
                <div class="card-header">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search web leads..." onkeyup="filterTable(this, 'webTable')">
                    </div>
                    <div class="card-actions">
                        <span class="lead-count-badge"><i class="fa-solid fa-list-check" style="margin-right: 5px;"></i>
                            Total: <strong><?php echo count($web_leads); ?></strong></span>
                        <a href="?action=export&type=web&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&exact_date=<?php echo urlencode($exact_date); ?>&sort=<?php echo urlencode($sort); ?>"
                            class="btn btn-outline"><i class="fa-solid fa-download"></i> Export CSV</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="webTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Service Package</th>
                                <th>Message</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($web_leads)): ?>
                                <tr>
                                    <td colspan="7" class="no-leads">
                                        <i class="fa-regular fa-folder-open"></i>
                                        No web leads captured yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($web_leads as $lead):
                                    $lead_js = array_merge($lead, ['lead_type' => 'web', 'display_type' => 'Web Design']);
                                    ?>
                                    <tr onclick="onRowClick(event, <?php echo htmlspecialchars(json_encode($lead_js)); ?>)"
                                        oncontextmenu="showContextMenu(event, 'web', <?php echo $lead['id']; ?>, <?php echo htmlspecialchars(json_encode($lead_js)); ?>)"
                                        class="<?php echo !$lead['is_read'] ? 'unread-row' : ''; ?>" data-id="<?php echo $lead['id']; ?>"
                                        style="cursor: pointer;">
                                        <td>
                                            <?php if (!$lead['is_read']): ?>
                                                <span class="unread-dot"></span>
                                            <?php endif; ?>
                                            #<?php echo $lead['id']; ?>
                                        </td>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($lead['name']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['phone']); ?></td>
                                        <td>
                                            <span class="badge badge-web" style="font-size:10px;">
                                                <?php echo htmlspecialchars($lead['service']); ?>
                                            </span>
                                        </td>
                                        <td class="text-truncate-cell"><?php echo htmlspecialchars($lead['message']); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($lead['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. SEO LEADS TAB -->
        <div class="dashboard-section <?php echo $active_tab === 'seo' ? 'active' : ''; ?>" id="tab-seo">
            <div class="content-header">
                <div class="header-title">
                    <h1>SEO Leads</h1>
                    <p>Enquiries collected from the free SEO audit audits.</p>
                </div>
            </div>

            <?php renderFilterBar($active_tab, $from_date, $to_date, $exact_date, $sort); ?>

            <div class="table-card" style="margin-top: 20px;">
                <div class="card-header">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search SEO leads..." onkeyup="filterTable(this, 'seoTable')">
                    </div>
                    <div class="card-actions">
                        <span class="lead-count-badge"><i class="fa-solid fa-list-check" style="margin-right: 5px;"></i>
                            Total: <strong><?php echo count($seo_leads); ?></strong></span>
                        <a href="?action=export&type=seo&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&exact_date=<?php echo urlencode($exact_date); ?>&sort=<?php echo urlencode($sort); ?>"
                            class="btn btn-outline"><i class="fa-solid fa-download"></i> Export CSV</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="seoTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Business Name</th>
                                <th>Website</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Primary Need</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($seo_leads)): ?>
                                <tr>
                                    <td colspan="8" class="no-leads">
                                        <i class="fa-regular fa-folder-open"></i>
                                        No SEO leads captured yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($seo_leads as $lead):
                                    $lead_js = array_merge($lead, ['lead_type' => 'seo', 'display_type' => 'SEO']);
                                    ?>
                                    <tr onclick="onRowClick(event, <?php echo htmlspecialchars(json_encode($lead_js)); ?>)"
                                        oncontextmenu="showContextMenu(event, 'seo', <?php echo $lead['id']; ?>, <?php echo htmlspecialchars(json_encode($lead_js)); ?>)"
                                        class="<?php echo !$lead['is_read'] ? 'unread-row' : ''; ?>" data-id="<?php echo $lead['id']; ?>"
                                        style="cursor: pointer;">
                                        <td>
                                            <?php if (!$lead['is_read']): ?>
                                                <span class="unread-dot"></span>
                                            <?php endif; ?>
                                            #<?php echo $lead['id']; ?>
                                        </td>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($lead['name']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['business_name']); ?></td>
                                        <td class="text-truncate-cell">
                                            <a href="<?php echo (strpos($lead['website'], 'http') === 0 ? '' : 'https://') . htmlspecialchars($lead['website']); ?>"
                                                target="_blank" style="color:var(--info); text-decoration:none;">
                                                <?php echo htmlspecialchars($lead['website']); ?> <i
                                                    class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['phone']); ?></td>
                                        <td>
                                            <span class="badge badge-seo" style="font-size:10px;">
                                                <?php echo htmlspecialchars($lead['seo_need']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($lead['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. SMM LEADS TAB -->
        <div class="dashboard-section <?php echo $active_tab === 'smm' ? 'active' : ''; ?>" id="tab-smm">
            <div class="content-header">
                <div class="header-title">
                    <h1>Social Media Marketing Leads</h1>
                    <p>Enquiries collected from the free SMM audits & Instagram roadmaps.</p>
                </div>
            </div>

            <?php renderFilterBar($active_tab, $from_date, $to_date, $exact_date, $sort); ?>

            <div class="table-card" style="margin-top: 20px;">
                <div class="card-header">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search SMM leads..." onkeyup="filterTable(this, 'smmTable')">
                    </div>
                    <div class="card-actions">
                        <span class="lead-count-badge"><i class="fa-solid fa-list-check" style="margin-right: 5px;"></i>
                            Total: <strong><?php echo count($smm_leads); ?></strong></span>
                        <a href="?action=export&type=smm&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&exact_date=<?php echo urlencode($exact_date); ?>&sort=<?php echo urlencode($sort); ?>"
                            class="btn btn-outline"><i class="fa-solid fa-download"></i> Export CSV</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="smmTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Business Name</th>
                                <th>IG Handle / Web</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Primary Need</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($smm_leads)): ?>
                                <tr>
                                    <td colspan="8" class="no-leads">
                                        <i class="fa-regular fa-folder-open"></i>
                                        No SMM leads captured yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($smm_leads as $lead):
                                    $lead_js = array_merge($lead, ['lead_type' => 'smm', 'display_type' => 'Social Media']);
                                    ?>
                                    <tr onclick="onRowClick(event, <?php echo htmlspecialchars(json_encode($lead_js)); ?>)"
                                        oncontextmenu="showContextMenu(event, 'smm', <?php echo $lead['id']; ?>, <?php echo htmlspecialchars(json_encode($lead_js)); ?>)"
                                        class="<?php echo !$lead['is_read'] ? 'unread-row' : ''; ?>" data-id="<?php echo $lead['id']; ?>"
                                        style="cursor: pointer;">
                                        <td>
                                            <?php if (!$lead['is_read']): ?>
                                                <span class="unread-dot"></span>
                                            <?php endif; ?>
                                            #<?php echo $lead['id']; ?>
                                        </td>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($lead['name']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['business_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['instagram_or_website']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['phone']); ?></td>
                                        <td>
                                            <span class="badge badge-smm" style="font-size:10px;">
                                                <?php echo htmlspecialchars($lead['smm_need']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($lead['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 5. AUTOMATION LEADS TAB -->
        <div class="dashboard-section <?php echo $active_tab === 'automation' ? 'active' : ''; ?>" id="tab-automation">
            <div class="content-header">
                <div class="header-title">
                    <h1>WhatsApp Automation Leads</h1>
                    <p>Enquiries collected from the free WhatsApp chatbot automation demo requests.</p>
                </div>
            </div>

            <?php renderFilterBar($active_tab, $from_date, $to_date, $exact_date, $sort); ?>

            <div class="table-card" style="margin-top: 20px;">
                <div class="card-header">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search automation leads..." onkeyup="filterTable(this, 'automationTable')">
                    </div>
                    <div class="card-actions">
                        <span class="lead-count-badge"><i class="fa-solid fa-list-check" style="margin-right: 5px;"></i>
                            Total: <strong><?php echo count($automation_leads); ?></strong></span>
                        <a href="?action=export&type=automation&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&exact_date=<?php echo urlencode($exact_date); ?>&sort=<?php echo urlencode($sort); ?>"
                            class="btn btn-outline"><i class="fa-solid fa-download"></i> Export CSV</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="automationTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Business Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Business Type</th>
                                <th>Message</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($automation_leads)): ?>
                                <tr>
                                    <td colspan="8" class="no-leads">
                                        <i class="fa-regular fa-folder-open"></i>
                                        No automation leads captured yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($automation_leads as $lead):
                                    $lead_js = array_merge($lead, ['lead_type' => 'automation', 'display_type' => 'Automation']);
                                    ?>
                                    <tr onclick="onRowClick(event, <?php echo htmlspecialchars(json_encode($lead_js)); ?>)"
                                        oncontextmenu="showContextMenu(event, 'automation', <?php echo $lead['id']; ?>, <?php echo htmlspecialchars(json_encode($lead_js)); ?>)"
                                        class="<?php echo !$lead['is_read'] ? 'unread-row' : ''; ?>" data-id="<?php echo $lead['id']; ?>"
                                        style="cursor: pointer;">
                                        <td>
                                            <?php if (!$lead['is_read']): ?>
                                                <span class="unread-dot"></span>
                                            <?php endif; ?>
                                            #<?php echo $lead['id']; ?>
                                        </td>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($lead['name']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['business_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['phone']); ?></td>
                                        <td>
                                            <span class="badge badge-automation" style="font-size:10px;">
                                                <?php echo htmlspecialchars($lead['business_type'] ?: 'Other'); ?>
                                            </span>
                                        </td>
                                        <td class="text-truncate-cell"><?php echo htmlspecialchars($lead['message']); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($lead['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 5. SECURITY TAB -->
        <div class="dashboard-section <?php echo $active_tab === 'security' ? 'active' : ''; ?>" id="tab-security">
            <!-- Centered Change Password Form -->
            <div style="flex-grow: 1; display: flex; align-items: center; justify-content: center; width: 100%;">
                <div class="table-card"
                    style="width: 100%; max-width: 450px; padding: 35px; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);">
                    <div style="text-align: center; margin-bottom: 25px;">
                        <div
                            style="width: 54px; height: 54px; border-radius: 14px; background: var(--primary-glow); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 15px;">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <h2
                            style="font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; color: #111827; margin-bottom: 5px;">
                            Change Password</h2>
                        <p style="font-size: 13px; color: var(--text-muted);">Ensure your admin account remains secure
                            by updating your credentials.</p>
                    </div>

                    <?php if (!empty($password_err)): ?>
                        <div
                            style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; text-align: left;">
                            <i class="fa-solid fa-circle-exclamation" style="margin-right: 8px;"></i>
                            <?php echo htmlspecialchars($password_err); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" autocomplete="off">
                        <input type="hidden" name="action" value="change_password">

                        <!-- Dummy inputs to catch browser autofill -->
                        <input type="password" name="current_password"
                            style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;"
                            tabindex="-1" autocomplete="off">
                        <input type="password" name="new_password"
                            style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;"
                            tabindex="-1" autocomplete="off">
                        <input type="password" name="confirm_password"
                            style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;"
                            tabindex="-1" autocomplete="off">

                        <div class="form-group" style="margin-bottom: 18px; text-align: left;">
                            <label for="current_password"
                                style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">Current
                                Password</label>
                            <div class="password-input-wrapper" style="position: relative;">
                                <input type="password" id="current_password" name="cur_pass_val" required
                                    class="filter-control" style="width: 100%; padding-right: 40px; height: 42px;"
                                    autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                                <button type="button" onclick="togglePasswordVisibility('current_password', this)"
                                    style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-light); cursor: pointer; font-size: 16px;">
                                    <i class="fa-regular fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 18px; text-align: left;">
                            <label for="new_password"
                                style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">New
                                Password</label>
                            <div class="password-input-wrapper" style="position: relative;">
                                <input type="password" id="new_password" name="new_pass_val" required
                                    class="filter-control" style="width: 100%; padding-right: 40px; height: 42px;"
                                    autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                                <button type="button" onclick="togglePasswordVisibility('new_password', this)"
                                    style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-light); cursor: pointer; font-size: 16px;">
                                    <i class="fa-regular fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 25px; text-align: left;">
                            <label for="confirm_password"
                                style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">Confirm
                                New Password</label>
                            <div class="password-input-wrapper" style="position: relative;">
                                <input type="password" id="confirm_password" name="cfm_pass_val" required
                                    class="filter-control" style="width: 100%; padding-right: 40px; height: 42px;"
                                    autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                                <button type="button" onclick="togglePasswordVisibility('confirm_password', this)"
                                    style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-light); cursor: pointer; font-size: 16px;">
                                    <i class="fa-regular fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary"
                            style="width: 100%; height: 45px; justify-content: center; border-radius: 12px; font-size: 14px; font-weight: 600;">
                            <i class="fa-solid fa-key"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- Lead Detail Modal -->
    <div class="modal" id="detailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-address-card"></i>
                    <span id="modalLeadType">Lead Details</span>
                </div>
                <button class="modal-close" onclick="closeLeadModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalDetailsBody">
                <!-- Populated via Javascript -->
            </div>
        </div>
    </div>

    <!-- Custom Context Menu -->
    <div id="customContextMenu" class="context-menu" style="display: none; position: absolute; z-index: 10000;">
        <ul>
            <li onclick="contextView()"><i class="fa-regular fa-eye"></i> View Details</li>
            <li onclick="contextDelete()" class="delete"><i class="fa-regular fa-trash-can"></i> Delete Lead</li>
        </ul>
    </div>

    <script>
        let activeContextLead = null;
        let activeContextType = null;
        let activeContextId = null;

        function showContextMenu(e, type, id, lead) {
            e.preventDefault();
            activeContextLead = lead;
            activeContextType = type;
            activeContextId = id;

            const menu = document.getElementById('customContextMenu');
            menu.style.display = 'block';
            menu.style.left = e.pageX + 'px';
            menu.style.top = e.pageY + 'px';

            // Add document click listener to dismiss the menu
            setTimeout(() => {
                document.addEventListener('click', closeContextMenu);
            }, 10);
        }

        function closeContextMenu() {
            const menu = document.getElementById('customContextMenu');
            if (menu) menu.style.display = 'none';
            document.removeEventListener('click', closeContextMenu);
        }

        function contextView() {
            if (activeContextLead) {
                showLeadDetails(activeContextLead);
            }
        }

        function contextDelete() {
            if (activeContextType && activeContextId) {
                if (confirm('Are you sure you want to delete this lead?')) {
                    window.location.href = `?action=delete&type=${activeContextType}&id=${activeContextId}`;
                }
            }
        }

        function onRowClick(e, lead) {
            // Prevent opening the modal if clicked on a hyperlink
            if (e.target.tagName.toLowerCase() === 'a' || e.target.closest('a')) {
                return;
            }
            showLeadDetails(lead);
        }

        // Hide context menu when pressing Escape or clicking outside
        window.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeContextMenu();
                closeLeadModal();
            }
        });

        // Lead details modal display
        function showLeadDetails(lead) {
            const modal = document.getElementById('detailsModal');
            const modalTitle = document.getElementById('modalLeadType');
            const body = document.getElementById('modalDetailsBody');

            // Mark as read via AJAX if unread
            if (!lead.is_read) {
                const formData = new FormData();
                formData.append('action', 'mark_as_read');
                formData.append('type', lead.lead_type);
                formData.append('id', lead.id);

                fetch('index.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mark as read locally so we don't request again
                        lead.is_read = 1;

                        // Find the row and remove unread styling
                        const row = document.querySelector(`tr[data-id="${lead.id}"].unread-row`);
                        if (row) {
                            row.classList.remove('unread-row');
                            const dot = row.querySelector('.unread-dot');
                            if (dot) dot.remove();
                        }

                        // Decrement sidebar badge count
                        const badge = document.getElementById('badge-' + lead.lead_type);
                        if (badge) {
                            let count = parseInt(badge.textContent) || 0;
                            if (count > 1) {
                                badge.textContent = count - 1;
                            } else {
                                badge.textContent = '0';
                                badge.style.display = 'none';
                            }
                        }
                    }
                })
                .catch(err => console.error('Error marking lead as read:', err));
            }

            modalTitle.innerHTML = `<span class="badge badge-${lead.lead_type}">${lead.display_type}</span> Lead #${lead.id}`;

            let detailsHtml = `
            <div class="modal-details-grid">
                <div class="detail-card">
                    <span class="detail-label"><i class="fa-regular fa-user" style="color:var(--primary); margin-right: 6px;"></i> Name</span>
                    <span class="detail-value">${escapeHtml(lead.name)}</span>
                </div>
                <div class="detail-card">
                    <span class="detail-label"><i class="fa-regular fa-envelope" style="color:var(--primary); margin-right: 6px;"></i> Email</span>
                    <span class="detail-value" style="word-break: break-all;">${escapeHtml(lead.email)}</span>
                </div>
                <div class="detail-card">
                    <span class="detail-label"><i class="fa-solid fa-phone" style="color:var(--primary); margin-right: 6px;"></i> Phone</span>
                    <span class="detail-value">${escapeHtml(lead.phone || 'N/A')}</span>
                </div>
            `;

            if (lead.lead_type === 'web') {
                detailsHtml += `
                <div class="detail-card">
                    <span class="detail-label"><i class="fa-solid fa-layer-group" style="color:var(--info); margin-right: 6px;"></i> Service Package</span>
                    <span class="detail-value">${escapeHtml(lead.service)}</span>
                </div>
                <div class="detail-card grid-span-2">
                    <span class="detail-label"><i class="fa-regular fa-comment-dots" style="color:var(--info); margin-right: 6px;"></i> Message</span>
                    <div class="detail-value message-box" style="margin-top: 5px; background: #ffffff;">${escapeHtml(lead.message || 'No message provided.')}</div>
                </div>
                `;
            } else if (lead.lead_type === 'seo') {
                detailsHtml += `
                <div class="detail-card">
                    <span class="detail-label"><i class="fa-solid fa-building" style="color:var(--success); margin-right: 6px;"></i> Business Name</span>
                    <span class="detail-value">${escapeHtml(lead.business_name)}</span>
                </div>
                <div class="detail-card">
                    <span class="detail-label"><i class="fa-solid fa-globe" style="color:var(--success); margin-right: 6px;"></i> Website URL</span>
                    <span class="detail-value">
                        <a href="${lead.website.startsWith('http') ? '' : 'https://'}${escapeHtml(lead.website)}" target="_blank" style="color:var(--info); text-decoration: none; font-weight: 600;">
                            ${escapeHtml(lead.website)} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 10px; margin-left: 2px;"></i>
                        </a>
                    </span>
                </div>
                <div class="detail-card grid-span-2">
                    <span class="detail-label"><i class="fa-solid fa-chart-line" style="color:var(--success); margin-right: 6px;"></i> Biggest SEO Need</span>
                    <span class="detail-value">${escapeHtml(lead.seo_need)}</span>
                </div>
                `;
            } else if (lead.lead_type === 'smm') {
                detailsHtml += `
                <div class="detail-card">
                    <span class="detail-label"><i class="fa-solid fa-building" style="color:var(--warning); margin-right: 6px;"></i> Business Name</span>
                    <span class="detail-value">${escapeHtml(lead.business_name)}</span>
                </div>
                <div class="detail-card">
                    <span class="detail-label"><i class="fa-brands fa-instagram" style="color:var(--warning); margin-right: 6px;"></i> IG Handle / Web</span>
                    <span class="detail-value">${escapeHtml(lead.instagram_or_website)}</span>
                </div>
                <div class="detail-card grid-span-2">
                    <span class="detail-label"><i class="fa-solid fa-bullhorn" style="color:var(--warning); margin-right: 6px;"></i> Primary SMM Need</span>
                    <span class="detail-value">${escapeHtml(lead.smm_need)}</span>
                </div>
                `;
            } else if (lead.lead_type === 'automation') {
                detailsHtml += `
                <div class="detail-card">
                    <span class="detail-label"><i class="fa-solid fa-building" style="color:#16a34a; margin-right: 6px;"></i> Business Name</span>
                    <span class="detail-value">${escapeHtml(lead.business_name)}</span>
                </div>
                <div class="detail-card">
                    <span class="detail-label"><i class="fa-solid fa-industry" style="color:#16a34a; margin-right: 6px;"></i> Business Type</span>
                    <span class="detail-value">${escapeHtml(lead.business_type || 'Other')}</span>
                </div>
                <div class="detail-card grid-span-2">
                    <span class="detail-label"><i class="fa-regular fa-comment-dots" style="color:#16a34a; margin-right: 6px;"></i> Message</span>
                    <div class="detail-value message-box" style="margin-top: 5px; background: #ffffff;">${escapeHtml(lead.message || 'No message provided.')}</div>
                </div>
                `;
            }

            detailsHtml += `
                <div class="detail-card grid-span-2">
                    <span class="detail-label"><i class="fa-regular fa-calendar-days" style="color:var(--text-light); margin-right: 6px;"></i> Captured At</span>
                    <span class="detail-value">${new Date(lead.created_at).toLocaleString()}</span>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; align-items: center; margin-top: 20px; gap: 15px;">
                <a href="?action=delete&type=${lead.lead_type}&id=${lead.id}" 
                   class="btn btn-danger" 
                   style="padding: 8px 16px; border-radius: 10px; font-size: 13px;"
                   onclick="return confirm('Are you sure you want to delete this lead?');">
                    <i class="fa-regular fa-trash-can"></i> Delete Lead
                </a>
            </div>
            `;

            body.innerHTML = detailsHtml;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLeadModal() {
            document.getElementById('detailsModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close modal when clicking outside
        window.addEventListener('click', function (e) {
            const modal = document.getElementById('detailsModal');
            if (e.target === modal) {
                closeLeadModal();
            }
        });

        // Helper to escape HTML tags
        function escapeHtml(text) {
            if (!text) return '';
            return text
                .toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Local filter search for tables
        function filterTable(input, tableId) {
            const filter = input.value.toLowerCase();
            const table = document.getElementById(tableId);
            const trs = table.getElementsByTagName('tr');

            for (let i = 1; i < trs.length; i++) {
                const tr = trs[i];
                // Skip "no leads" row
                if (tr.querySelector('.no-leads')) continue;

                let match = false;
                const tds = tr.getElementsByTagName('td');
                for (let j = 0; j < tds.length; j++) {
                    if (tds[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        match = true;
                        break;
                    }
                }
                tr.style.display = match ? '' : 'none';
            }
        }

        // Toggle password fields visibility
        function togglePasswordVisibility(inputId, btnEl) {
            const input = document.getElementById(inputId);
            const icon = btnEl.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }

        // ── Mobile sidebar toggle ──────────────────────────────────────────
        (function () {
            const hamburger   = document.getElementById('hamburgerBtn');
            const mobileSb    = document.getElementById('mobileSidebar');
            const overlay     = document.getElementById('sidebarOverlay');

            function openSidebar() {
                if (mobileSb) mobileSb.classList.add('msb-open');
                if (overlay)  overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                if (mobileSb) mobileSb.classList.remove('msb-open');
                if (overlay)  overlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (hamburger) hamburger.addEventListener('click', openSidebar);
            if (overlay)   overlay.addEventListener('click', closeSidebar);

            // Close when any mobile nav link is tapped
            if (mobileSb) {
                mobileSb.querySelectorAll('.msb-link').forEach(function (link) {
                    link.addEventListener('click', closeSidebar);
                });
            }
        })();
    </script>

</body>

</html>