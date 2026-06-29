<?php
$active_tab = 'dashboard';
require_once __DIR__ . '/includes/header.php';

$filter_tab = isset($_GET['filter_tab']) ? $_GET['filter_tab'] : '';
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : date('Y-m');

// Fetch Data
$kpis = getDashboardKPIs($pdo, $my_permissions, $is_super_admin, $filter_tab, $filter_month);
$chartData = getMonthlyChartData($pdo, $my_permissions, $is_super_admin, 3);
$untouchedLeads = getRecentUntouchedLeads($pdo, $my_permissions, $is_super_admin, 5);

$tabNames = [
    'web' => 'Web Leads',
    'seo' => 'SEO Leads',
    'smm' => 'SMM Leads',
    'automation' => 'Automation Leads'
];
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="dashboard-section active" id="tab-dashboard" style="text-align: left; width: 100%;">
    
    <div class="content-header" style="margin-bottom: 25px; width: 100%; justify-content: flex-start; text-align: left;">
        <div class="header-title" style="text-align: left; width: 100%;">
            <h1>Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($current_display); ?>! Here is your performance overview.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div style="margin-bottom: 25px; width: 100%;">
        <form method="GET" action="" style="display: flex; align-items: center; gap: 15px;">
            <label for="filter_tab" style="font-weight: 600; color: #4b5563;"><i class="fa-solid fa-filter"></i> Filter KPIs by Service:</label>
            <select name="filter_tab" id="filter_tab" onchange="this.form.submit()" style="padding: 10px 15px; border-radius: 8px; border: 1px solid #d1d5db; outline: none; font-family: 'Outfit', sans-serif; font-size: 14px; min-width: 200px;">
                <option value="">All Services</option>
                <?php foreach ($tabNames as $tabKey => $tabName): ?>
                    <?php if (canAccess($tabKey, $is_super_admin, $my_permissions)): ?>
                        <option value="<?php echo htmlspecialchars($tabKey); ?>" <?php echo $filter_tab === $tabKey ? 'selected' : ''; ?>><?php echo htmlspecialchars($tabName); ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            
            <input type="month" name="filter_month" id="filter_month" value="<?php echo htmlspecialchars($filter_month); ?>" onchange="this.form.submit()" style="padding: 9px 15px; border-radius: 8px; border: 1px solid #d1d5db; outline: none; font-family: 'Outfit', sans-serif; font-size: 14px;">
            
            <?php if ($filter_tab !== '' || $filter_month !== date('Y-m')): ?>
                <a href="dashboard.php" class="btn btn-outline" style="padding: 9px 15px; font-size: 14px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-solid fa-rotate-left"></i> Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; width: 100%;">
        <!-- Total Leads -->
        <div class="table-card" style="padding: 20px; border-left: 4px solid var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 13px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Leads</div>
                    <div style="font-size: 28px; font-weight: 700; color: #111827; margin-top: 5px;"><?php echo number_format($kpis['total_leads']); ?></div>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.1); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 20px;">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>

        <!-- Deals Won -->
        <div class="table-card" style="padding: 20px; border-left: 4px solid var(--success);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 13px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Deals Won</div>
                    <div style="font-size: 28px; font-weight: 700; color: #111827; margin-top: 5px;"><?php echo number_format($kpis['deals_won']); ?></div>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center; color: var(--success); font-size: 20px;">
                    <i class="fa-solid fa-trophy"></i>
                </div>
            </div>
        </div>

        <!-- Pending Follow-ups -->
        <div class="table-card" style="padding: 20px; border-left: 4px solid #f59e0b;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 13px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Pending Follow-ups</div>
                    <div style="font-size: 28px; font-weight: 700; color: #111827; margin-top: 5px;"><?php echo number_format($kpis['pending_followups']); ?></div>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); display: flex; align-items: center; justify-content: center; color: #f59e0b; font-size: 20px;">
                    <i class="fa-regular fa-clock"></i>
                </div>
            </div>
        </div>

        <!-- Conversion Rate -->
        <div class="table-card" style="padding: 20px; border-left: 4px solid #ec4899;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 13px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Conversion Rate</div>
                    <div style="font-size: 28px; font-weight: 700; color: #111827; margin-top: 5px;"><?php echo $kpis['conversion_rate']; ?>%</div>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(236, 72, 153, 0.1); display: flex; align-items: center; justify-content: center; color: #ec4899; font-size: 20px;">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart and Needs Attention Row -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; width: 100%; margin-bottom: 30px;">
        
        <!-- Chart Section -->
        <div class="table-card" style="padding: 24px; height: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0;"><i class="fa-solid fa-chart-column" style="color: var(--primary); margin-right: 8px;"></i> Monthly Performance Overview</h3>
                <div style="display: flex; gap: 10px;">
                    <button onclick="updateChart('leads_captured')" id="btn-chart-leads" class="btn btn-primary" style="padding: 6px 14px; font-size: 13px; border-radius: 8px;">Leads Captured</button>
                    <button onclick="updateChart('deals_won')" id="btn-chart-deals" class="btn btn-outline" style="padding: 6px 14px; font-size: 13px; border-radius: 8px; background: white; border: 1px solid #d1d5db; color: #4b5563;">Deals Won</button>
                </div>
            </div>
            <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        <!-- Needs Attention -->
        <div class="table-card" style="padding: 24px; height: 100%;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 15px 0;"><i class="fa-solid fa-circle-exclamation" style="color: #ef4444; margin-right: 8px;"></i> Needs Attention (Recent Untouched)</h3>
            
            <?php if (empty($untouchedLeads)): ?>
                <div style="padding: 30px; text-align: center; color: var(--text-muted); background: #f9fafb; border-radius: 12px; border: 1px dashed #e5e7eb;">
                    <i class="fa-regular fa-face-smile" style="font-size: 24px; margin-bottom: 10px; color: var(--success);"></i>
                    <p>Great job! You have no untouched leads right now.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Service</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($untouchedLeads as $lead): ?>
                                <tr>
                                    <td style="font-weight: 600; white-space: nowrap;"><?php echo htmlspecialchars($lead['name']); ?></td>
                                    <td><span class="badge" style="background: rgba(79,70,229,0.1); color: var(--primary); font-size: 11px; padding: 2px 6px;"><?php echo $tabNames[$lead['tab_type']] ?? $lead['tab_type']; ?></span></td>
                                    <td style="max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($lead['email']); ?>"><?php echo htmlspecialchars($lead['email']); ?></td>
                                    <td>
                                        <a href="leads_<?php echo $lead['tab_type']; ?>.php" class="btn btn-outline" style="padding: 2px 6px; font-size: 11px; border-radius: 4px;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
const rawChartData = <?php echo json_encode($chartData); ?>;
const labels = rawChartData.labels;

// Colors for the 4 tabs
const colors = {
    web: { bg: 'rgba(79, 70, 229, 0.8)', border: 'rgba(79, 70, 229, 1)' },
    seo: { bg: 'rgba(16, 185, 129, 0.8)', border: 'rgba(16, 185, 129, 1)' },
    smm: { bg: 'rgba(236, 72, 153, 0.8)', border: 'rgba(236, 72, 153, 1)' },
    automation: { bg: 'rgba(245, 158, 11, 0.8)', border: 'rgba(245, 158, 11, 1)' }
};

const tabDisplayNames = {
    web: 'Web Leads',
    seo: 'SEO Leads',
    smm: 'SMM Leads',
    automation: 'Automation'
};

let chartInstance = null;

function buildDatasets(type) {
    const dataObj = rawChartData.datasets[type];
    const datasets = [];
    for (const tab in dataObj) {
        if (dataObj.hasOwnProperty(tab)) {
            datasets.push({
                label: tabDisplayNames[tab] || tab,
                data: dataObj[tab],
                backgroundColor: colors[tab] ? colors[tab].bg : '#9ca3af',
                borderColor: colors[tab] ? colors[tab].border : '#6b7280',
                borderWidth: 1,
                borderRadius: 4
            });
        }
    }
    return datasets;
}

function updateChart(type) {
    // Update buttons
    if (type === 'leads_captured') {
        document.getElementById('btn-chart-leads').className = 'btn btn-primary';
        document.getElementById('btn-chart-leads').style.background = '';
        document.getElementById('btn-chart-leads').style.border = '';
        document.getElementById('btn-chart-leads').style.color = '';
        
        document.getElementById('btn-chart-deals').className = 'btn btn-outline';
        document.getElementById('btn-chart-deals').style.background = 'white';
        document.getElementById('btn-chart-deals').style.border = '1px solid #d1d5db';
        document.getElementById('btn-chart-deals').style.color = '#4b5563';
    } else {
        document.getElementById('btn-chart-deals').className = 'btn btn-primary';
        document.getElementById('btn-chart-deals').style.background = '';
        document.getElementById('btn-chart-deals').style.border = '';
        document.getElementById('btn-chart-deals').style.color = '';
        
        document.getElementById('btn-chart-leads').className = 'btn btn-outline';
        document.getElementById('btn-chart-leads').style.background = 'white';
        document.getElementById('btn-chart-leads').style.border = '1px solid #d1d5db';
        document.getElementById('btn-chart-leads').style.color = '#4b5563';
    }

    const datasets = buildDatasets(type);

    if (chartInstance) {
        chartInstance.data.datasets = datasets;
        chartInstance.update();
    } else {
        const ctx = document.getElementById('performanceChart').getContext('2d');
        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 20,
                            font: { family: "'Outfit', sans-serif", size: 13 }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        titleFont: { family: "'Outfit', sans-serif", size: 14 },
                        bodyFont: { family: "'Outfit', sans-serif", size: 13 },
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        stacked: false,
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { family: "'Outfit', sans-serif" } }
                    },
                    y: {
                        stacked: false,
                        beginAtZero: true,
                        grid: { color: '#f3f4f6', drawBorder: false },
                        ticks: {
                            stepSize: 1,
                            font: { family: "'Outfit', sans-serif" }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }
}

// Initialize chart with Leads Captured on page load
document.addEventListener('DOMContentLoaded', function() {
    updateChart('leads_captured');
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
