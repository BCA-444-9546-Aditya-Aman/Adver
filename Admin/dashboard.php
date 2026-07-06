<?php
$active_tab = 'dashboard';
require_once __DIR__ . '/includes/header.php';

$filter_tab = isset($_GET['filter_tab']) ? $_GET['filter_tab'] : '';
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';

// Fetch Data
$kpis = getDashboardKPIs($pdo, $my_permissions, $is_super_admin, $filter_tab, $filter_month);
$chartData = getMonthlyChartData($pdo, $my_permissions, $is_super_admin, 3);
$pieData = getDashboardPieChartData($pdo, $my_permissions, $is_super_admin, $filter_tab, $filter_month);

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
            <p>Welcome back, <strong style="font-weight: 700; color: #111827;"><?php echo htmlspecialchars($current_display); ?></strong>! Here is your performance overview.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div style="margin-bottom: 25px; width: 100%;">
        <form method="GET" action="" class="filter-bar" style="margin-bottom: 25px; display: flex; align-items: center; gap: 12px; padding: 10px 15px; flex-wrap: wrap;">
            <div class="filter-group">
                <label for="filter_tab" style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 5px;"><i class="fa-solid fa-filter"></i> Service:</label>
                <select name="filter_tab" id="filter_tab" class="filter-control" onchange="this.form.submit()" style="font-size: 12px; padding: 4px 8px; min-width: 150px;">
                    <option value="">All Services</option>
                    <?php foreach ($tabNames as $tabKey => $tabName): ?>
                        <?php if (canAccess($tabKey, $is_super_admin, $my_permissions)): ?>
                            <option value="<?php echo htmlspecialchars($tabKey); ?>" <?php echo $filter_tab === $tabKey ? 'selected' : ''; ?>><?php echo htmlspecialchars($tabName); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_month" style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 5px;"><i class="fa-regular fa-calendar-days"></i> Month:</label>
                <input type="month" name="filter_month" id="filter_month" class="filter-control" style="font-size: 12px; padding: 4px 8px; max-width: 110px;" value="<?php echo htmlspecialchars($filter_month); ?>" onchange="this.form.submit()">
            </div>
            
            <?php if ($filter_tab !== '' || $filter_month !== ''): ?>
                <div style="display: flex; gap: 6px; margin-left: auto;">
                    <a href="dashboard.php" class="btn btn-outline btn-sm" style="padding: 4px 8px; font-size: 12px; border-radius: 6px;"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-container" id="kpiCardsContainer">
        <!-- Total Leads -->
        <div class="table-card" style="padding: 20px; border-left: 4px solid var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 13px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Leads</div>
                    <div style="font-size: 28px; font-weight: 700; color: #111827; margin-top: 5px;"><span id="dashboard-total-leads"><?php echo number_format($kpis['total_leads']); ?></span></div>
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
                    <div style="font-size: 28px; font-weight: 700; color: #111827; margin-top: 5px;"><span id="dashboard-deals-won"><?php echo number_format($kpis['deals_won']); ?></span></div>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center; color: var(--success); font-size: 20px;">
                    <i class="fa-solid fa-crown"></i>
                </div>
            </div>
        </div>
 
        <!-- Talks in Progress -->
        <div class="table-card" style="padding: 20px; border-left: 4px solid #f59e0b;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 13px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Talks in Progress</div>
                    <div style="font-size: 28px; font-weight: 700; color: #111827; margin-top: 5px;"><span id="dashboard-talks-in-progress"><?php echo number_format($kpis['pending_followups']); ?></span></div>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); display: flex; align-items: center; justify-content: center; color: #f59e0b; font-size: 20px;">
                    <i class="fa-solid fa-comments"></i>
                </div>
            </div>
        </div>
 
        <!-- Conversion Rate -->
        <div class="table-card" style="padding: 20px; border-left: 4px solid #ec4899;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 13px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Conversion Rate</div>
                    <div style="font-size: 28px; font-weight: 700; color: #111827; margin-top: 5px;"><span id="dashboard-conversion-rate"><?php echo $kpis['conversion_rate']; ?></span>%</div>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(236, 72, 153, 0.1); display: flex; align-items: center; justify-content: center; color: #ec4899; font-size: 20px;">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Dot Indicators for Mobile KPI Carousel -->
    <div class="kpi-dots-container">
        <span class="kpi-dot active" onclick="scrollToKpi(0)"></span>
        <span class="kpi-dot" onclick="scrollToKpi(1)"></span>
        <span class="kpi-dot" onclick="scrollToKpi(2)"></span>
        <span class="kpi-dot" onclick="scrollToKpi(3)"></span>
    </div>

    <!-- Chart and Needs Attention Row -->
    <div class="dashboard-grid">
        
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

        <!-- Pie Chart Section -->
        <div class="table-card" style="padding: 24px; height: 100%;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 20px 0;"><i class="fa-solid fa-chart-pie" style="color: #6366f1; margin-right: 8px;"></i> Lead Conversion & Status Breakdown</h3>
            <?php if ($pieData['total'] == 0): ?>
                <div style="padding: 40px 20px; text-align: center; color: var(--text-muted); background: #f9fafb; border-radius: 12px; border: 1px dashed #e5e7eb; height: calc(100% - 40px); display: flex; flex-direction: column; justify-content: center; align-items: center;">
                    <i class="fa-solid fa-chart-pie" style="font-size: 32px; margin-bottom: 12px; color: #cbd5e1;"></i>
                    <p style="margin: 0; font-size: 14px;">No data available for the selected filters.</p>
                </div>
            <?php else: ?>
                <div style="position: relative; height: 200px; width: 100%; display: flex; justify-content: center; align-items: center;">
                    <canvas id="pieChart"></canvas>
                </div>
                <!-- Mini legends with percentages and counts -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 25px; text-align: center; font-size: 12px; border-top: 1px solid #f3f4f6; padding-top: 15px;">
                    <div>
                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #10b981; margin-right: 4px;"></span>
                        <span style="font-weight: 600; color: #4b5563;">Won: <?php echo $pieData['won']; ?></span>
                        <div style="font-size: 11px; color: #9ca3af; margin-top: 2px;"><?php echo $pieData['won_percent']; ?>%</div>
                    </div>
                    <div>
                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #ef4444; margin-right: 4px;"></span>
                        <span style="font-weight: 600; color: #4b5563;">Lost: <?php echo $pieData['lost']; ?></span>
                        <div style="font-size: 11px; color: #9ca3af; margin-top: 2px;"><?php echo $pieData['lost_percent']; ?>%</div>
                    </div>
                    <div>
                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #f59e0b; margin-right: 4px;"></span>
                        <span style="font-weight: 600; color: #4b5563;">Pending: <?php echo $pieData['pending']; ?></span>
                        <div style="font-size: 11px; color: #9ca3af; margin-top: 2px;"><?php echo $pieData['pending_percent']; ?>%</div>
                    </div>
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
    
    // Initialize Pie/Doughnut Chart
    const pieData = <?php echo json_encode($pieData); ?>;
    if (document.getElementById('pieChart') && pieData.total > 0) {
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Won (Closed - Won)', 'Lost (Closed - Lost)', 'Pending / Active'],
                datasets: [{
                    data: [pieData.won, pieData.lost, pieData.pending],
                    backgroundColor: [
                        '#10b981', // green
                        '#ef4444', // red
                        '#f59e0b'  // amber
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        titleFont: { family: "'Outfit', sans-serif", size: 13 },
                        bodyFont: { family: "'Outfit', sans-serif", size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return ` ${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
});

// Sync KPI Carousel Dots on Mobile
const kpiContainer = document.getElementById('kpiCardsContainer');
const dots = document.querySelectorAll('.kpi-dot');
if (kpiContainer && dots.length > 0) {
    kpiContainer.addEventListener('scroll', () => {
        const width = kpiContainer.getBoundingClientRect().width;
        const index = Math.round(kpiContainer.scrollLeft / width);
        dots.forEach((dot, i) => {
            if (i === index) dot.classList.add('active');
            else dot.classList.remove('active');
        });
    });
}

function scrollToKpi(index) {
    const kpiContainer = document.getElementById('kpiCardsContainer');
    if (kpiContainer) {
        const width = kpiContainer.getBoundingClientRect().width;
        kpiContainer.scrollTo({
            left: index * width,
            behavior: 'smooth'
        });
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
