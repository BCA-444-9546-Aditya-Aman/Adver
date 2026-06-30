<?php
$active_tab = 'automation';
require_once __DIR__ . '/includes/header.php';

$from_date     = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date       = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$filter_month  = isset($_GET['filter_month']) ? $_GET['filter_month'] : (empty($from_date) && empty($to_date) ? date('Y-m') : '');
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$sort          = isset($_GET['sort']) && strtolower($_GET['sort']) === 'asc' ? 'asc' : 'desc';

$automation_leads = getLeads($pdo, 'automation_leads', $filter_month, $filter_status, $sort, $from_date, $to_date, $current_admin_id);

$total_leads = count($automation_leads);
$total_won = 0; $total_lost = 0;
foreach ($automation_leads as $l) {
    if ($l['latest_status'] === 'Closed - Won') $total_won++;
    elseif ($l['latest_status'] === 'Closed - Lost') $total_lost++;
}
$total_pending = $total_leads - ($total_won + $total_lost);
?>

<div class="dashboard-section active" id="tab-automation">
    <div class="content-header"><div class="header-title"><h1>WhatsApp Automation Leads</h1><p>Enquiries collected from the automation service page.</p></div></div>

    <div class="metrics-cards-container" id="metricsCardsContainer">
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid var(--border-color); display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fa-solid fa-users"></i></div>
            <div>
                <div style="font-size: 13px; color: var(--text-light); font-weight: 600;">Total Leads</div>
                <div style="font-size: 24px; font-weight: 700; color: var(--text-dark); line-height: 1.2;"><span id="metric-total"><?php echo $total_leads; ?></span></div>
            </div>
        </div>
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid var(--border-color); display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fa-solid fa-handshake"></i></div>
            <div>
                <div style="font-size: 13px; color: var(--text-light); font-weight: 600;">Total Won</div>
                <div style="font-size: 24px; font-weight: 700; color: var(--text-dark); line-height: 1.2;"><span id="metric-won"><?php echo $total_won; ?></span></div>
            </div>
        </div>
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid var(--border-color); display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(245, 158, 11, 0.1); color: var(--warning); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fa-solid fa-hourglass-half"></i></div>
            <div>
                <div style="font-size: 13px; color: var(--text-light); font-weight: 600;">Total Pending</div>
                <div style="font-size: 24px; font-weight: 700; color: var(--text-dark); line-height: 1.2;"><span id="metric-pending"><?php echo $total_pending; ?></span></div>
            </div>
        </div>
    </div>

    <!-- Dot Indicators for Mobile Metrics Carousel -->
    <div class="metrics-dots-container">
        <span class="metrics-dot active" onclick="scrollToMetric(0)"></span>
        <span class="metrics-dot" onclick="scrollToMetric(1)"></span>
        <span class="metrics-dot" onclick="scrollToMetric(2)"></span>
    </div>

    <?php renderFilterBar($active_tab, $filter_month, $filter_status, $sort, $from_date, $to_date); ?>
    <div class="table-card" style="margin-top: 20px;">
        <div class="card-header">
            <div class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="automation_lead_fltr" name="automation_lead_fltr_no_fill" placeholder="Search automation leads..." onkeyup="filterTable(this, 'automationTable')" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');" value=""></div>
            <div class="card-actions">
                <span class="lead-count-badge"><i class="fa-solid fa-list-check" style="margin-right: 5px;"></i> Total: <strong><span id="header-total-count"><?php echo count($automation_leads); ?></span></strong></span>
                <a href="export.php?type=automation&filter_month=<?php echo urlencode($filter_month); ?>&filter_status=<?php echo urlencode($filter_status); ?>&sort=<?php echo urlencode($sort); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>" class="btn btn-outline"><i class="fa-solid fa-download"></i> Export CSV</a>
            </div>
        </div>
        <div class="table-responsive">
            <table id="automationTable">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Service</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($automation_leads)): ?>
                    <tr><td colspan="5" class="no-leads"><i class="fa-regular fa-folder-open"></i> No automation leads captured yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($automation_leads as $lead): $lead_js = array_merge($lead, ['lead_type' => 'automation', 'display_type' => 'Automation']); ?>
                    <tr class="lead-row <?php echo !$lead['is_read'] ? 'unread-row' : ''; ?>"
                        data-id="<?php echo $lead['id']; ?>"
                        data-type="automation"
                        data-lead='<?php echo htmlspecialchars(json_encode($lead_js, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT), ENT_HTML5, 'UTF-8'); ?>'
                        onclick="rowClick(this,event)"
                        oncontextmenu="rowContext(this,event)"
                        style="cursor: pointer;">
<?php
$status_label = $lead['latest_status'] ?: 'Untouched';
$map_status_classes = ['Qualified'=>'status-qualified','Initial Contact Made'=>'status-contacted','Proposal Sent'=>'status-proposal','In Discussion'=>'status-discussion','Follow-Up Scheduled'=>'status-followup','No Response'=>'status-noresponse','Closed - Won'=>'status-won','Closed - Lost'=>'status-lost'];
$status_class = $map_status_classes[$status_label] ?? 'status-noresponse';
if ($status_label === 'Untouched') $status_class = 'status-noresponse';
?>
                        <td style="font-weight: 600;">
                            <?php if (!$lead['is_read']): ?><span class="unread-dot"></span><?php endif; ?>
                            <?php echo htmlspecialchars($lead['name']); ?>
                        </td>
                        <td><a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>" onclick="event.stopPropagation()" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($lead['email']); ?></a></td>
                        <td><a href="tel:<?php echo htmlspecialchars($lead['phone']); ?>" onclick="event.stopPropagation()" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($lead['phone']); ?></a></td>
                        <td><span class="badge badge-automation" style="font-size:10px;"><?php echo htmlspecialchars($lead['business_type'] ?: 'Other'); ?></span></td>
                        <td><span class="timeline-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status_label); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
