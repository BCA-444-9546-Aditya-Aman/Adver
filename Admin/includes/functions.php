<?php

function canAccess($tab, $is_super_admin, $my_permissions) {
    if ($is_super_admin) return true;
    return isset($my_permissions[$tab]) ? $my_permissions[$tab] : false;
}

function getLeadsCount($pdo, $table, $from_date, $to_date, $exact_date) {
    try {
        $sql = "SELECT COUNT(*) FROM $table WHERE 1=1"; $params = [];
        if (!empty($exact_date)) { $sql .= " AND DATE(created_at) = ?"; $params[] = $exact_date; }
        else {
            if (!empty($from_date)) { $sql .= " AND DATE(created_at) >= ?"; $params[] = $from_date; }
            if (!empty($to_date))   { $sql .= " AND DATE(created_at) <= ?"; $params[] = $to_date; }
        }
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (\PDOException $e) { return 0; }
}

function getUnreadLeadsCount($pdo, $table) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE is_read = 0");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (\PDOException $e) { return 0; }
}

function getLeads($pdo, $table, $filter_month, $filter_status, $sort) {
    try {
        $lead_type = str_replace('_leads', '', $table);
        $sql = "SELECT t.*, (
                    SELECT lsu.status 
                    FROM lead_status_updates lsu 
                    WHERE lsu.lead_type = '$lead_type' AND lsu.lead_id = t.id 
                    ORDER BY lsu.updated_at DESC LIMIT 1
                ) AS latest_status 
                FROM `$table` t WHERE 1=1";
        $params = [];
        if (!empty($filter_month)) {
            $sql .= " AND DATE_FORMAT(t.created_at, '%Y-%m') = ?";
            $params[] = $filter_month;
        }
        if (!empty($filter_status)) {
            if ($filter_status === 'Untouched') {
                $sql .= " HAVING latest_status IS NULL";
            } else {
                $sql .= " HAVING latest_status = ?";
                $params[] = $filter_status;
            }
        }
        $sql .= " ORDER BY t.created_at " . ($sort === 'asc' ? 'ASC' : 'DESC');
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) { return []; }
}

function renderFilterBar($active_tab, $filter_month, $filter_status, $sort) { 
    $statuses = ['Untouched', 'Qualified', 'Initial Contact Made', 'Proposal Sent', 'In Discussion', 'Follow-Up Scheduled', 'No Response', 'Closed - Won', 'Closed - Lost'];
?>
    <form method="GET" action="" class="filter-bar" style="margin-bottom: 25px; margin-top: 15px;">
        <div class="filter-group">
            <label for="filter_month"><i class="fa-regular fa-calendar-days"></i> Month/Year:</label>
            <input type="month" id="filter_month" name="filter_month" class="filter-control" value="<?php echo htmlspecialchars($filter_month); ?>" onchange="this.form.submit()">
        </div>
        <div class="filter-group">
            <label for="filter_status"><i class="fa-solid fa-filter"></i> Status:</label>
            <select id="filter_status" name="filter_status" class="filter-control" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach ($statuses as $st): ?>
                <option value="<?php echo htmlspecialchars($st); ?>" <?php echo $filter_status === $st ? 'selected' : ''; ?>><?php echo htmlspecialchars($st); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="sort"><i class="fa-solid fa-arrow-down-z-a"></i> Sort By Date:</label>
            <select id="sort" name="sort" class="filter-control" onchange="this.form.submit()">
                <option value="desc" <?php echo $sort === 'desc' ? 'selected' : ''; ?>>Newest First</option>
                <option value="asc"  <?php echo $sort === 'asc'  ? 'selected' : ''; ?>>Oldest First</option>
            </select>
        </div>
        <div style="display: flex; gap: 8px; margin-left: auto;">
            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-rotate-left"></i> Reset</a>
        </div>
    </form>
<?php }

// Dashboard Data Aggregation Functions
function getDashboardKPIs($pdo, $permissions, $is_super_admin, $filter_tab = '', $filter_month = '') {
    $tabs = ['web', 'seo', 'smm', 'automation'];
    $allowed_tabs = array_filter($tabs, function($tab) use ($is_super_admin, $permissions) {
        return canAccess($tab, $is_super_admin, $permissions);
    });

    if (!empty($filter_tab) && in_array($filter_tab, $allowed_tabs)) {
        $allowed_tabs = [$filter_tab];
    }

    $kpis = [
        'total_leads' => 0,
        'deals_won' => 0,
        'pending_followups' => 0,
        'conversion_rate' => 0
    ];

    if (empty($allowed_tabs)) return $kpis;
    
    $dateFilterLeads = "";
    $dateFilterStatus = "";
    if (!empty($filter_month)) {
        // Prevent SQL injection by validating format (YYYY-MM)
        if (preg_match('/^\d{4}-\d{2}$/', $filter_month)) {
            $dateFilterLeads = " WHERE DATE_FORMAT(created_at, '%Y-%m') = '$filter_month'";
            $dateFilterStatus = " AND DATE_FORMAT(lsu.created_at, '%Y-%m') = '$filter_month'";
        }
    }

    foreach ($allowed_tabs as $tab) {
        $table = "{$tab}_leads";
        
        // Total leads
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`$dateFilterLeads");
            $kpis['total_leads'] += (int)$stmt->fetchColumn();
        } catch (\PDOException $e) {}

        // Deals won
        try {
            $stmt = $pdo->query("SELECT COUNT(DISTINCT lsu.lead_id) FROM lead_status_updates lsu WHERE lsu.lead_type = '$tab' AND lsu.status = 'Closed - Won'$dateFilterStatus");
            $kpis['deals_won'] += (int)$stmt->fetchColumn();
        } catch (\PDOException $e) {}

        // Pending followups
        try {
            $stmt = $pdo->query("SELECT COUNT(DISTINCT lsu.lead_id) FROM lead_status_updates lsu WHERE lsu.lead_type = '$tab' AND lsu.status = 'Follow-Up Scheduled'$dateFilterStatus");
            $kpis['pending_followups'] += (int)$stmt->fetchColumn();
        } catch (\PDOException $e) {}
    }

    if ($kpis['total_leads'] > 0) {
        $kpis['conversion_rate'] = round(($kpis['deals_won'] / $kpis['total_leads']) * 100, 1);
    }

    return $kpis;
}

function getMonthlyChartData($pdo, $permissions, $is_super_admin, $months = 6) {
    $tabs = ['web', 'seo', 'smm', 'automation'];
    $allowed_tabs = array_filter($tabs, function($tab) use ($is_super_admin, $permissions) {
        return canAccess($tab, $is_super_admin, $permissions);
    });

    $chart_data = [
        'labels' => [],
        'datasets' => [
            'leads_captured' => [], // Tab => [count1, count2...]
            'deals_won' => []
        ]
    ];

    if (empty($allowed_tabs)) return $chart_data;

    // Generate last N months labels (e.g. Jan 2026, Feb 2026)
    for ($i = $months - 1; $i >= 0; $i--) {
        $chart_data['labels'][] = date('M Y', strtotime("-$i months"));
    }

    foreach ($allowed_tabs as $tab) {
        $chart_data['datasets']['leads_captured'][$tab] = array_fill(0, $months, 0);
        $chart_data['datasets']['deals_won'][$tab] = array_fill(0, $months, 0);
        $table = "{$tab}_leads";

        // Leads captured per month
        try {
            $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%b %Y') as month_label, COUNT(*) as count FROM `$table` WHERE created_at >= DATE_SUB(NOW(), INTERVAL $months MONTH) GROUP BY month_label");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $idx = array_search($row['month_label'], $chart_data['labels']);
                if ($idx !== false) $chart_data['datasets']['leads_captured'][$tab][$idx] = (int)$row['count'];
            }
        } catch (\PDOException $e) {}

        // Deals won per month
        try {
            $stmt = $pdo->query("SELECT DATE_FORMAT(updated_at, '%b %Y') as month_label, COUNT(DISTINCT lead_id) as count FROM lead_status_updates WHERE lead_type = '$tab' AND status = 'Closed - Won' AND updated_at >= DATE_SUB(NOW(), INTERVAL $months MONTH) GROUP BY month_label");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $idx = array_search($row['month_label'], $chart_data['labels']);
                if ($idx !== false) $chart_data['datasets']['deals_won'][$tab][$idx] = (int)$row['count'];
            }
        } catch (\PDOException $e) {}
    }

    return $chart_data;
}

function getRecentUntouchedLeads($pdo, $permissions, $is_super_admin, $limit = 5) {
    $tabs = ['web', 'seo', 'smm', 'automation'];
    $allowed_tabs = array_filter($tabs, function($tab) use ($is_super_admin, $permissions) {
        return canAccess($tab, $is_super_admin, $permissions);
    });

    $untouched_leads = [];
    if (empty($allowed_tabs)) return $untouched_leads;

    foreach ($allowed_tabs as $tab) {
        $table = "{$tab}_leads";
        try {
            // Find leads with no status updates
            $sql = "SELECT t.id, t.name, t.email, t.created_at, '$tab' as tab_type 
                    FROM `$table` t 
                    LEFT JOIN lead_status_updates lsu ON lsu.lead_type = '$tab' AND lsu.lead_id = t.id 
                    WHERE lsu.id IS NULL 
                    ORDER BY t.created_at DESC LIMIT $limit";
            $stmt = $pdo->query($sql);
            $untouched_leads = array_merge($untouched_leads, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\PDOException $e) {}
    }

    // Sort by created_at DESC across all tabs
    usort($untouched_leads, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    return array_slice($untouched_leads, 0, $limit);
}
