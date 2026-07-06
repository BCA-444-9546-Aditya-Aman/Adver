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

function getUnreadLeadsCount($pdo, $table, $admin_id = 0) {
    try {
        $lead_type = str_replace('_leads', '', $table);
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM `$table` t 
            WHERE t.id NOT IN (
                SELECT arl.lead_id FROM admin_read_leads arl 
                WHERE arl.admin_id = ? AND arl.lead_type = ?
            )
        ");
        $stmt->execute([$admin_id, $lead_type]);
        return (int)$stmt->fetchColumn();
    } catch (\PDOException $e) { return 0; }
}

function getLeads($pdo, $table, $filter_month, $filter_status, $sort, $from_date = '', $to_date = '', $admin_id = 0) {
    try {
        $lead_type = str_replace('_leads', '', $table);
        $admin_id_val = (int)$admin_id;
        $sql = "SELECT t.*, (
                    SELECT COUNT(*) FROM admin_read_leads arl 
                    WHERE arl.admin_id = $admin_id_val AND arl.lead_type = '$lead_type' AND arl.lead_id = t.id
                ) AS is_read_by_me, (
                    SELECT lsu.status 
                    FROM lead_status_updates lsu 
                    WHERE lsu.lead_type = '$lead_type' AND lsu.lead_id = t.id 
                    ORDER BY lsu.updated_at DESC LIMIT 1
                ) AS latest_status, (
                    SELECT lsu.updated_at 
                    FROM lead_status_updates lsu 
                    WHERE lsu.lead_type = '$lead_type' AND lsu.lead_id = t.id 
                    ORDER BY lsu.updated_at DESC LIMIT 1
                ) AS latest_status_date
                FROM `$table` t WHERE 1=1";
        $params = [];
        if (!empty($filter_month)) {
            $sql .= " AND t.created_at LIKE ?";
            $params[] = $filter_month . '%';
        }
        if (!empty($from_date) && empty($to_date)) {
            $sql .= " AND DATE(t.created_at) = ?";
            $params[] = $from_date;
        } elseif (!empty($from_date) && !empty($to_date)) {
            $sql .= " AND DATE(t.created_at) BETWEEN ? AND ?";
            $params[] = $from_date;
            $params[] = $to_date;
        }
        if (!empty($filter_status)) {
            if ($filter_status === 'Untouched') {
                $sql .= " HAVING latest_status IS NULL";
            } else {
                $sql .= " HAVING latest_status = ?";
                $params[] = $filter_status;
            }
        }
        $order = ($sort === 'asc') ? 'ASC' : 'DESC';
        $sql .= " ORDER BY 
                  CASE WHEN latest_status IN ('Closed - Won', 'Closed - Lost') THEN 1 ELSE 0 END ASC,
                  CASE WHEN latest_status IN ('Closed - Won', 'Closed - Lost') THEN latest_status_date ELSE t.created_at END $order";
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($leads as &$lead) {
            $lead['is_read'] = ((int)$lead['is_read_by_me'] > 0) ? 1 : 0;
        }
        return $leads;
    } catch (\PDOException $e) { return []; }
}

function renderFilterBar($active_tab, $filter_month, $filter_status, $sort, $from_date = '', $to_date = '') { 
    $statuses = ['Untouched', 'Qualified', 'Initial Contact Made', 'Proposal Sent', 'In Discussion', 'Follow-Up Scheduled', 'No Response', 'Closed - Won', 'Closed - Lost'];
?>
    <form method="GET" action="" class="filter-bar" style="margin-bottom: 25px; margin-top: 15px; display: flex; align-items: center; gap: 12px; padding: 10px 15px; flex-wrap: wrap;">
        <div class="filter-group">
            <label for="filter_month" style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 5px;"><i class="fa-regular fa-calendar-days"></i> Month:</label>
            <input type="month" id="filter_month" name="filter_month" class="filter-control" style="font-size: 12px; padding: 4px 8px; max-width: 110px;" value="<?php echo htmlspecialchars($filter_month); ?>" onchange="this.form.submit()">
        </div>
        
        <div class="filter-group">
            <label for="from_date" style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 5px;"><i class="fa-solid fa-calendar-day"></i> From:</label>
            <input type="date" id="from_date" name="from_date" class="filter-control" style="font-size: 12px; padding: 4px 8px; max-width: 115px; min-width: auto;" value="<?php echo htmlspecialchars($from_date); ?>" onchange="this.form.submit()">
        </div>
        
        <div class="filter-group">
            <label for="to_date" style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 5px;"><i class="fa-solid fa-calendar-day"></i> To:</label>
            <input type="date" id="to_date" name="to_date" class="filter-control" style="font-size: 12px; padding: 4px 8px; max-width: 115px; min-width: auto;" value="<?php echo htmlspecialchars($to_date); ?>" onchange="this.form.submit()">
        </div>

        <div class="filter-group">
            <label for="filter_status" style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 5px;"><i class="fa-solid fa-filter"></i> Status:</label>
            <select id="filter_status" name="filter_status" class="filter-control" style="font-size: 12px; padding: 4px 8px; max-width: 130px;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach ($statuses as $st): ?>
                <option value="<?php echo htmlspecialchars($st); ?>" <?php echo $filter_status === $st ? 'selected' : ''; ?>><?php echo htmlspecialchars($st); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="sort" style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 5px;"><i class="fa-solid fa-arrow-down-z-a"></i> Sort:</label>
            <select id="sort" name="sort" class="filter-control" style="font-size: 12px; padding: 4px 8px; max-width: 100px;" onchange="this.form.submit()">
                <option value="desc" <?php echo $sort === 'desc' ? 'selected' : ''; ?>>Newest</option>
                <option value="asc"  <?php echo $sort === 'asc'  ? 'selected' : ''; ?>>Oldest</option>
            </select>
        </div>
        
        <div style="display: flex; gap: 6px; margin-left: auto;">
            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-outline btn-sm" style="padding: 4px 8px; font-size: 12px; border-radius: 6px;"><i class="fa-solid fa-arrow-rotate-left"></i> Reset</a>
        </div>
    </form>
<?php }

// Dashboard Data Aggregation Functions
function getDashboardKPIs($pdo, $permissions, $is_super_admin, $filter_tab = '', $filter_month = '') {
    $pieData = getDashboardPieChartData($pdo, $permissions, $is_super_admin, $filter_tab, $filter_month);
    
    $kpis = [
        'total_leads' => $pieData['total'],
        'deals_won' => $pieData['won'],
        'pending_followups' => $pieData['pending'],
        'conversion_rate' => 0
    ];

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

function getDashboardPieChartData($pdo, $permissions, $is_super_admin, $filter_tab = '', $filter_month = '') {
    $tabs = ['web', 'seo', 'smm', 'automation'];
    $allowed_tabs = array_filter($tabs, function($tab) use ($is_super_admin, $permissions) {
        return canAccess($tab, $is_super_admin, $permissions);
    });

    if (!empty($filter_tab) && in_array($filter_tab, $allowed_tabs)) {
        $allowed_tabs = [$filter_tab];
    }

    $data = [
        'won' => 0,
        'lost' => 0,
        'pending' => 0,
        'total' => 0,
        'won_percent' => 0,
        'lost_percent' => 0,
        'pending_percent' => 0
    ];

    if (empty($allowed_tabs)) return $data;

    foreach ($allowed_tabs as $tab) {
        $table = "{$tab}_leads";
        $sql = "SELECT t.id, (
                    SELECT lsu.status 
                    FROM lead_status_updates lsu 
                    WHERE lsu.lead_type = '$tab' AND lsu.lead_id = t.id 
                    ORDER BY lsu.updated_at DESC LIMIT 1
                ) AS latest_status 
                FROM `$table` t WHERE 1=1";
        $params = [];
        if (!empty($filter_month)) {
            $sql .= " AND t.created_at LIKE ?";
            $params[] = $filter_month . '%';
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($leads as $l) {
                $data['total']++;
                $status = $l['latest_status'];
                if ($status === 'Closed - Won') {
                    $data['won']++;
                } elseif ($status === 'Closed - Lost') {
                    $data['lost']++;
                } else {
                    $data['pending']++;
                }
            }
        } catch (\PDOException $e) {}
    }

    if ($data['total'] > 0) {
        $data['won_percent'] = round(($data['won'] / $data['total']) * 100, 1);
        $data['lost_percent'] = round(($data['lost'] / $data['total']) * 100, 1);
        $data['pending_percent'] = round(($data['pending'] / $data['total']) * 100, 1);
    }

    return $data;
}

function getSpecificAdminPerformance($pdo, $admin_id, $filter_month = '') {
    $admin_id = (int)$admin_id;
    $month_cond = '';
    $params = [];
    if (!empty($filter_month)) {
        $month_cond = " AND al.created_at LIKE ?";
        $params[] = $filter_month . '%';
    }

    $lead_union = "
        SELECT id, 'web' as lead_type, created_at FROM web_leads
        UNION ALL
        SELECT id, 'seo' as lead_type, created_at FROM seo_leads
        UNION ALL
        SELECT id, 'smm' as lead_type, created_at FROM smm_leads
        UNION ALL
        SELECT id, 'automation' as lead_type, created_at FROM automation_leads
    ";

    $stats = [
        'admin_id' => $admin_id,
        'handled_count' => 0,
        'won_count' => 0,
        'lost_count' => 0,
        'conversion_rate' => 0.0,
        'avg_response_minutes' => null,
        'avg_conversion_minutes' => null
    ];

    try {
        // 1. Leads Handled: unique leads updated by this admin
        $sql = "SELECT COUNT(DISTINCT lsu.lead_id, lsu.lead_type) 
                FROM lead_status_updates lsu
                JOIN ($lead_union) al ON al.id = lsu.lead_id AND al.lead_type = lsu.lead_type
                WHERE lsu.updated_by = $admin_id $month_cond";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stats['handled_count'] = (int)$stmt->fetchColumn();

        // 2. Deals Won: leads whose CURRENT latest status is 'Closed - Won' and was closed by this admin
        $sql = "SELECT COUNT(DISTINCT lsu.lead_id, lsu.lead_type) 
                FROM lead_status_updates lsu
                JOIN (
                    SELECT lead_id, lead_type, MAX(id) as max_id
                    FROM lead_status_updates
                    GROUP BY lead_id, lead_type
                ) latest ON lsu.id = latest.max_id
                JOIN ($lead_union) al ON al.id = lsu.lead_id AND al.lead_type = lsu.lead_type
                WHERE lsu.updated_by = $admin_id AND lsu.status = 'Closed - Won' $month_cond";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stats['won_count'] = (int)$stmt->fetchColumn();

        // 3. Deals Lost: leads whose CURRENT latest status is 'Closed - Lost' and was closed by this admin
        $sql = "SELECT COUNT(DISTINCT lsu.lead_id, lsu.lead_type) 
                FROM lead_status_updates lsu
                JOIN (
                    SELECT lead_id, lead_type, MAX(id) as max_id
                    FROM lead_status_updates
                    GROUP BY lead_id, lead_type
                ) latest ON lsu.id = latest.max_id
                JOIN ($lead_union) al ON al.id = lsu.lead_id AND al.lead_type = lsu.lead_type
                WHERE lsu.updated_by = $admin_id AND lsu.status = 'Closed - Lost' $month_cond";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stats['lost_count'] = (int)$stmt->fetchColumn();

        // Conversion Rate
        if ($stats['handled_count'] > 0) {
            $stats['conversion_rate'] = round(($stats['won_count'] / $stats['handled_count']) * 100, 1);
        }

        // 4. Avg First Response Time (in minutes)
        $sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, al.created_at, lsu.first_update_time))
                FROM (
                    SELECT lead_id, lead_type, updated_by, MIN(updated_at) as first_update_time
                    FROM lead_status_updates
                    GROUP BY lead_id, lead_type
                ) lsu
                JOIN ($lead_union) al ON al.id = lsu.lead_id AND al.lead_type = lsu.lead_type
                WHERE lsu.updated_by = $admin_id $month_cond";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetchColumn();
        $stats['avg_response_minutes'] = $res !== null ? round((float)$res, 1) : null;

        // 5. Avg Conversion Time (in minutes)
        $sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, al.created_at, lsu.updated_at))
                FROM lead_status_updates lsu
                JOIN ($lead_union) al ON al.id = lsu.lead_id AND al.lead_type = lsu.lead_type
                WHERE lsu.updated_by = $admin_id AND lsu.status IN ('Closed - Won', 'Closed - Lost') $month_cond";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetchColumn();
        $stats['avg_conversion_minutes'] = $res !== null ? round((float)$res, 1) : null;

    } catch (\PDOException $e) {
        // Fail silent
    }

    return $stats;
}

function getAdminPerformanceLeaderboard($pdo, $filter_month = '') {
    $sql = "SELECT 
                au.id as admin_id,
                au.username,
                au.display_name,
                au.is_super_admin
            FROM admin_users au 
            WHERE au.is_super_admin = 0 
            ORDER BY au.display_name ASC";

    try {
        $stmt = $pdo->query($sql);
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $leaderboard = [];
        foreach ($admins as $admin) {
            $stats = getSpecificAdminPerformance($pdo, $admin['admin_id'], $filter_month);
            $stats['username'] = $admin['username'];
            $stats['display_name'] = $admin['display_name'];
            $stats['is_super_admin'] = $admin['is_super_admin'];
            
            // Fetch active permissions
            $my_perms = [];
            $p_stmt = $pdo->prepare("SELECT tab FROM admin_permissions WHERE admin_id = ? AND can_access = 1");
            $p_stmt->execute([$admin['admin_id']]);
            while ($p_row = $p_stmt->fetch(PDO::FETCH_ASSOC)) {
                $my_perms[] = $p_row['tab'];
            }
            $stats['permissions'] = $my_perms;
            
            $leaderboard[] = $stats;
        }
        
        // Sort leaderboard: Won Deals DESC, Conversion Rate DESC, Display Name ASC
        usort($leaderboard, function($a, $b) {
            if ($a['won_count'] !== $b['won_count']) {
                return $b['won_count'] <=> $a['won_count'];
            }
            if (abs($a['conversion_rate'] - $b['conversion_rate']) > 0.001) {
                return $b['conversion_rate'] > $a['conversion_rate'] ? 1 : -1;
            }
            return strcasecmp($a['display_name'], $b['display_name']);
        });
        
        return $leaderboard;
    } catch (\PDOException $e) {
        return [];
    }
}

function formatResponseTime($minutes) {
    if ($minutes === null) return 'N/A';
    if ($minutes < 60) {
        return round($minutes) . 'm';
    }
    $hours = floor($minutes / 60);
    $mins = round($minutes % 60);
    if ($hours < 24) {
        return "{$hours}h {$mins}m";
    }
    $days = floor($hours / 24);
    $rem_hours = $hours % 24;
    return "{$days}d {$rem_hours}h";
}


