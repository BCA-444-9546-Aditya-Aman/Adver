<?php
$active_tab = 'admins';
require_once __DIR__ . '/includes/header.php';

// Refetch sub-admins with permissions to display properly in the UI
$sub_admins = [];
if ($is_super_admin) {
    try {
        $sa_stmt = $pdo->query("SELECT id, username, email, created_at FROM admin_users WHERE is_super_admin = 0 ORDER BY username ASC");
        $sub_admins = $sa_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sub_admins as &$sa) {
            $p_stmt = $pdo->prepare("SELECT tab, can_access FROM admin_permissions WHERE admin_id = ?");
            $p_stmt->execute([$sa['id']]);
            $sa['permissions'] = $p_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($sa);
    } catch (\PDOException $e) {}
}
?>

<div class="dashboard-section active" id="tab-admins">
    <div class="content-header">
        <div class="header-title">
            <h1>Admin Management</h1>
            <p>Add and manage sub-admin accounts with granular tab-level access control.</p>
        </div>
    </div>

    <div class="admins-header-row" style="margin-top: 20px;">
        <div style="font-size: 13px; color: var(--text-muted);"><i class="fa-solid fa-users" style="margin-right: 6px;"></i> <strong><?php echo count($sub_admins); ?></strong> sub-admin<?php echo count($sub_admins) !== 1 ? 's' : ''; ?> registered</div>
        <button class="btn btn-primary" onclick="openAddAdminModal()" style="padding: 10px 22px; border-radius: 12px; font-size: 13px;"><i class="fa-solid fa-user-plus"></i> Add Sub-Admin</button>
    </div>

    <?php if (empty($sub_admins)): ?>
    <div class="no-admins-state">
        <i class="fa-solid fa-users-slash"></i>
        <p>No sub-admins yet. Click <strong>Add Sub-Admin</strong> to get started.</p>
    </div>
    <?php else: ?>
    <div class="table-card" style="margin-top: 20px;">
        <div class="table-responsive">
            <table class="table" id="adminsTable">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Added Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sub_admins as $adm): ?>
                    <tr style="cursor: pointer;" class="lead-row"
                        onclick="adminRowClick(<?php echo $adm['id']; ?>)"
                        oncontextmenu="adminRowContext(<?php echo $adm['id']; ?>, event)">
                        <td style="font-weight: 600;">@<?php echo htmlspecialchars($adm['username']); ?></td>
                        <td><?php echo htmlspecialchars($adm['email']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($adm['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<ul class="context-menu" id="adminContextMenu" style="display:none; position:absolute; z-index:9999;">
    <li id="ac_view"><i class="fa-regular fa-eye"></i> View Details</li>
    <li id="ac_delete" class="delete"><i class="fa-regular fa-trash-can"></i> Delete Admin</li>
</ul>

<script>
let activeAdminId = null;

function adminRowClick(id) {
    window.location.href = 'admin_detail.php?id=' + id;
}

function adminRowContext(id, e) {
    e.preventDefault();
    activeAdminId = id;
    const menu = document.getElementById('adminContextMenu');
    menu.style.display = 'block';
    
    let x = e.pageX, y = e.pageY;
    if (x + 200 > window.innerWidth + window.scrollX) x = x - 200;
    menu.style.left = x + 'px';
    menu.style.top = y + 'px';
}

document.addEventListener('click', function(e) {
    const menu = document.getElementById('adminContextMenu');
    if (menu) menu.style.display = 'none';
});

document.getElementById('ac_view').onclick = function(e) {
    if (activeAdminId) window.location.href = 'admin_detail.php?id=' + activeAdminId;
};

document.getElementById('ac_delete').onclick = function(e) {
    if (activeAdminId && confirm('Are you sure you want to delete this admin?')) {
        const fd = new FormData();
        fd.append('action', 'delete_admin');
        fd.append('admin_id', activeAdminId);
        fetch('includes/ajax_handler.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            if (data.success) { location.reload(); }
            else { alert(data.error); }
        });
    }
};
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
