<?php
$active_tab = 'analytics';
require_once __DIR__ . '/includes/header.php';

// Restrict to admin with access permission
if (!canAccess('analytics', $is_super_admin, $my_permissions)) {
    echo "<script>window.location.href='dashboard.php';</script>";
    exit;
}

$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';

// Fetch leaderboard data
$leaderboard = getAdminPerformanceLeaderboard($pdo, $filter_month);

// Map permissions keys to friendly labels
$tabNames = [
    'web' => 'Web',
    'seo' => 'SEO',
    'smm' => 'SMM',
    'automation' => 'Automation',
    'security' => 'Security'
];
?>

<div class="analytics-section active" style="text-align: left; width: 100%;">
    
    <!-- Header -->
    <div class="content-header" style="margin-bottom: 25px; width: 100%; justify-content: flex-start;">
        <div class="header-title" style="width: 100%;">
            <h1>Leaderboard</h1>
            <p>Track and rank administrator lead response times and conversion rates.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div style="margin-bottom: 25px; width: 100%;">
        <form method="GET" action="" class="filter-bar" style="margin-bottom: 25px; display: flex; align-items: center; gap: 12px; padding: 10px 15px; flex-wrap: wrap;">
            <div class="filter-group">
                <label for="filter_month" style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 5px;"><i class="fa-regular fa-calendar-days"></i> Month:</label>
                <input type="month" name="filter_month" id="filter_month" class="filter-control" style="font-size: 12px; padding: 4px 8px; max-width: 110px;" value="<?php echo htmlspecialchars($filter_month); ?>" onchange="this.form.submit()">
            </div>
            
            <?php if ($filter_month !== ''): ?>
                <div style="display: flex; gap: 6px; margin-left: auto;">
                    <a href="analytics.php" class="btn btn-outline btn-sm" style="padding: 4px 8px; font-size: 12px; border-radius: 6px;"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Leaderboard Card -->
    <div class="card">
        <div class="card-header" style="padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 700; color: #111827;"><i class="fa-solid fa-ranking-star" style="margin-right: 8px; color: var(--primary);"></i>Administrator Leaderboard</h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Individual details and performance rankings.</p>
            </div>
            <div style="position: relative; max-width: 250px; width: 100%;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 12px;"></i>
                <input type="search" id="leaderboardSearch" name="lead_search_no_fill" placeholder="Search administrator..." class="filter-control" style="font-size: 12px; padding: 6px 12px 6px 32px; width: 100%; border-radius: 8px;" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');" value="">
            </div>
        </div>
        
        <div class="table-responsive">
            <table style="width:100%; border-collapse: collapse; min-width: 800px;" id="leaderboardTable">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); text-align: left; background: #fbfbfa;">
                        <th style="padding: 14px 24px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-align: center; width: 80px;">Rank</th>
                        <th style="padding: 14px 24px; font-size: 12px; font-weight: 700; color: var(--text-muted);">Administrator</th>
                        <th style="padding: 14px 16px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-align: center;">Leads Handled</th>
                        <th style="padding: 14px 16px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-align: center;">Deals Won</th>
                        <th style="padding: 14px 16px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-align: center;">Deals Lost</th>
                        <th style="padding: 14px 16px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-align: center;">Conversion Rate</th>
                        <th style="padding: 14px 16px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-align: center;">Avg Response</th>
                        <th style="padding: 14px 16px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-align: center;">Avg Close Time</th>
                        <?php if ($is_super_admin): ?>
                        <th style="padding: 14px 24px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-align: right;">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaderboard)): ?>
                        <tr>
                            <td colspan="<?php echo $is_super_admin ? 9 : 8; ?>" style="padding: 40px; text-align: center; color: var(--text-muted); font-size: 14px;">No administrator performance data found.</td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $rank = 0;
                        foreach ($leaderboard as $admin): 
                            $rank++;
                        ?>
                            <tr style="border-bottom: 1px solid var(--border-color); font-size: 13px;" class="leaderboard-row">
                                <td style="padding: 16px 24px; text-align: center;">
                                    <?php if ($rank === 1): ?>
                                        <span class="badge" style="background: #fef3c7; color: #d97706; border: 1px solid #fcd34d; font-weight: 700; padding: 4px 10px; border-radius: 12px; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-medal" style="color:#fbbf24;"></i> 1st</span>
                                    <?php elseif ($rank === 2): ?>
                                        <span class="badge" style="background: #f3f4f6; color: #4b5563; border: 1px solid #d1d5db; font-weight: 700; padding: 4px 10px; border-radius: 12px; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-medal" style="color:#9ca3af;"></i> 2nd</span>
                                    <?php elseif ($rank === 3): ?>
                                        <span class="badge" style="background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; font-weight: 700; padding: 4px 10px; border-radius: 12px; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-medal" style="color:#b45309;"></i> 3rd</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: var(--bg-soft); color: var(--text-muted); border: 1px solid var(--border-color); font-weight: 600; padding: 4px 10px; border-radius: 12px; font-size: 11px; display: inline-block;">#<?php echo $rank; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 16px 24px; font-weight: 600; color: #111827;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-soft); color: var(--primary); font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color);">
                                            <?php 
                                                echo strtoupper($admin['username'][0].($admin['username'][1] ?? ''));
                                            ?>
                                        </div>
                                        <div>
                                            <span class="admin-name-text">@<?php echo htmlspecialchars($admin['username']); ?></span>
                                            <?php if ($admin['is_super_admin']): ?>
                                                <span class="badge" style="background: #fef3c7; color: #b45309; padding: 2px 6px; font-size: 10px; border-radius: 4px; border: 1px solid #fde68a; margin-left: 4px;"><i class="fa-solid fa-crown" style="margin-right:2px;"></i> Super Admin</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 16px 16px; text-align: center; font-weight: 600; color: #374151;"><?php echo $admin['handled_count']; ?></td>
                                <td style="padding: 16px 16px; text-align: center; font-weight: 600; color: #166534;"><?php echo $admin['won_count']; ?></td>
                                <td style="padding: 16px 16px; text-align: center; font-weight: 600; color: #991b1b;"><?php echo $admin['lost_count']; ?></td>
                                <td style="padding: 16px 16px; text-align: center;">
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                        <span style="font-weight: 700; color: #111827;"><?php echo $admin['conversion_rate']; ?>%</span>
                                        <div style="width: 60px; height: 4px; background: #e5e7eb; border-radius: 2px; overflow: hidden;">
                                            <div style="width: <?php echo $admin['conversion_rate']; ?>%; height: 100%; background: var(--primary); border-radius: 2px;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 16px 16px; text-align: center; font-weight: 500; color: #374151;"><?php echo formatResponseTime($admin['avg_response_minutes']); ?></td>
                                <td style="padding: 16px 16px; text-align: center; font-weight: 500; color: #374151;"><?php echo formatResponseTime($admin['avg_conversion_minutes']); ?></td>
                                <?php if ($is_super_admin): ?>
                                <td style="padding: 16px 24px; text-align: right;">
                                    <a href="admin_detail.php?id=<?php echo $admin['admin_id']; ?>&from=leaderboard" class="btn btn-outline btn-sm" style="padding: 4px 10px; font-size: 11px; border-radius: 6px;"><i class="fa-solid fa-user" style="margin-right: 4px;"></i> Details</a>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Leaderboard Table Search Filter ────────────────────────────────────
    const searchInput = document.getElementById('leaderboardSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.leaderboard-row');
            
            rows.forEach(row => {
                const nameSpan = row.querySelector('.admin-name-text');
                if (nameSpan) {
                    const name = nameSpan.textContent.toLowerCase();
                    if (name.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    }
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
