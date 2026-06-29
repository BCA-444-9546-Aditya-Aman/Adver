<?php
$active_tab = 'security';
require_once __DIR__ . '/includes/header.php';

$password_err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!canAccess('security', $is_super_admin, $my_permissions)) {
        die('Access denied');
    }
    
    $cur = $_POST['cur_pass_val'] ?? '';
    $new = $_POST['new_pass_val'] ?? '';
    $cfm = $_POST['cfm_pass_val'] ?? '';
    
    if (empty($cur) || empty($new) || empty($cfm)) {
        $password_err = "All fields are required.";
    } elseif ($new !== $cfm) {
        $password_err = "New passwords do not match.";
    } elseif (strlen($new) < 6) {
        $password_err = "New password must be at least 6 characters.";
    } else {
        $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
        $stmt->execute([$current_admin_id]);
        $hash = $stmt->fetchColumn();
        if ($hash && password_verify($cur, $hash)) {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
            if ($upd->execute([$new_hash, $current_admin_id])) {
                $_SESSION['msg'] = "Password changed successfully!";
                echo "<script>window.location.href='security.php';</script>";
                exit;
            } else {
                $password_err = "Something went wrong. Please try again.";
            }
        } else {
            $password_err = "Current password is incorrect.";
        }
    }
}
?>

<div class="dashboard-section active" id="tab-security" style="display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 130px);">
    <div class="table-card" style="width: 100%; max-width: 450px; padding: 35px; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);">
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="width: 54px; height: 54px; border-radius: 14px; background: var(--primary-glow); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 15px;"><i class="fa-solid fa-lock"></i></div>
                <h2 style="font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; color: #111827; margin-bottom: 5px;">Change Password</h2>
                <p style="font-size: 13px; color: var(--text-muted);">Ensure your admin account remains secure by updating your credentials.</p>
            </div>
            <?php if (!empty($password_err)): ?>
            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; text-align: left;">
                <i class="fa-solid fa-circle-exclamation" style="margin-right: 8px;"></i><?php echo htmlspecialchars($password_err); ?>
            </div>
            <?php endif; ?>
            <form method="POST" action="" autocomplete="off">
                <input type="hidden" name="action" value="change_password">
                <input type="password" name="current_password" style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;" tabindex="-1" autocomplete="off">
                <input type="password" name="new_password"     style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;" tabindex="-1" autocomplete="off">
                <input type="password" name="confirm_password" style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;" tabindex="-1" autocomplete="off">
                <div class="form-group" style="margin-bottom: 18px; text-align: left;">
                    <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">Current Password</label>
                    <div style="position: relative;">
                        <input type="password" id="current_password" name="cur_pass_val" required class="filter-control" style="width: 100%; padding-right: 40px; height: 42px;" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                        <button type="button" onclick="togglePasswordVisibility('current_password', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-light); cursor: pointer; font-size: 16px;"><i class="fa-regular fa-eye-slash"></i></button>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 18px; text-align: left;">
                    <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">New Password</label>
                    <div style="position: relative;">
                        <input type="password" id="new_password" name="new_pass_val" required class="filter-control" style="width: 100%; padding-right: 40px; height: 42px;" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                        <button type="button" onclick="togglePasswordVisibility('new_password', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-light); cursor: pointer; font-size: 16px;"><i class="fa-regular fa-eye-slash"></i></button>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 25px; text-align: left;">
                    <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">Confirm New Password</label>
                    <div style="position: relative;">
                        <input type="password" id="confirm_password" name="cfm_pass_val" required class="filter-control" style="width: 100%; padding-right: 40px; height: 42px;" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                        <button type="button" onclick="togglePasswordVisibility('confirm_password', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-light); cursor: pointer; font-size: 16px;"><i class="fa-regular fa-eye-slash"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 45px; justify-content: center; border-radius: 12px; font-size: 14px; font-weight: 600;"><i class="fa-solid fa-key"></i> Update Password</button>
            </form>
        </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
