<?php
$active_tab = 'security';
require_once __DIR__ . '/includes/header.php';

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_credentials') {
    if (!canAccess('security', $is_super_admin, $my_permissions)) {
        die('Access denied');
    }
    
    $new_user = trim($_POST['new_username_val'] ?? '');
    $cur_pass = $_POST['cur_pass_val'] ?? '';
    $new_pass = $_POST['new_pass_val'] ?? '';
    $cfm_pass = $_POST['cfm_pass_val'] ?? '';
    
    if (empty($cur_pass)) {
        $error_msg = "Current password is required to verify identity.";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE id = ?");
        $stmt->execute([$current_admin_id]);
        $hash = $stmt->fetchColumn();
        
        if (!$hash || !password_verify($cur_pass, $hash)) {
            $error_msg = "Current password is incorrect.";
        } else {
            $pdo->beginTransaction();
            $username_updated = false;
            $password_updated = false;
            
            // Check username update
            if (!empty($new_user) && $new_user !== $current_admin['username']) {
                if (strlen($new_user) < 3) {
                    $error_msg = "Username must be at least 3 characters.";
                } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_user)) {
                    $error_msg = "Username can only contain letters, numbers, and underscores.";
                } else {
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ? AND id != ?");
                    $chk->execute([$new_user, $current_admin_id]);
                    if ($chk->fetchColumn() > 0) {
                        $error_msg = "Username is already taken.";
                    } else {
                        $upd = $pdo->prepare("UPDATE admin_users SET username = ? WHERE id = ?");
                        $upd->execute([$new_user, $current_admin_id]);
                        $_SESSION['admin_username'] = $new_user;
                        $username_updated = true;
                    }
                }
            }
            
            // Check password update
            if (empty($error_msg) && (!empty($new_pass) || !empty($cfm_pass))) {
                if (empty($new_pass) || empty($cfm_pass)) {
                    $error_msg = "Both new password and confirm password fields are required.";
                } elseif ($new_pass !== $cfm_pass) {
                    $error_msg = "New passwords do not match.";
                } elseif (strlen($new_pass) < 6) {
                    $error_msg = "New password must be at least 6 characters.";
                } else {
                    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                    $upd->execute([$new_hash, $current_admin_id]);
                    $password_updated = true;
                }
            }
            
            if (empty($error_msg)) {
                $pdo->commit();
                
                if ($username_updated && $password_updated) {
                    $_SESSION['msg'] = "Username and password updated successfully!";
                } elseif ($username_updated) {
                    $_SESSION['msg'] = "Username updated successfully!";
                } elseif ($password_updated) {
                    $_SESSION['msg'] = "Password changed successfully!";
                } else {
                    $_SESSION['msg'] = "No changes were made.";
                }
                
                echo "<script>window.location.href='security.php';</script>";
                exit;
            } else {
                $pdo->rollBack();
            }
        }
    }
}
?>

<div class="dashboard-section active" id="tab-security" style="display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 130px); padding: 20px 0;">
    <div class="table-card" style="width: 100%; max-width: 700px; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); background: var(--card-bg); border: 1px solid var(--border-color);">
        
        <div style="text-align: center; margin-bottom: 25px;">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: var(--primary-glow); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px;"><i class="fa-solid fa-shield-halved"></i></div>
            <h2 style="font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; color: var(--text-dark); margin-bottom: 5px;">Security Settings</h2>
            <p style="font-size: 13px; color: var(--text-muted); max-width: 500px; margin: 0 auto;">Manage your account credentials. You can update your username and/or password. Confirming your current password is required to save changes.</p>
        </div>
        
        <?php if (!empty($error_msg)): ?>
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px 14px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; text-align: left; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-circle-exclamation" style="font-size: 15px;"></i><span><?php echo htmlspecialchars($error_msg); ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">
            <input type="hidden" name="action" value="update_credentials">
            <input type="password" name="prevent_autofill" style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;" tabindex="-1">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-bottom: 20px; text-align: left;">
                
                <!-- Left Column: Account Profile -->
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 2px;">
                        <h3 style="font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700; color: var(--text-dark); margin: 0; display: flex; align-items: center; gap: 8px;"><i class="fa-regular fa-user" style="color: var(--primary);"></i> Account Details</h3>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">Current Username</label>
                        <input type="text" class="filter-control" style="width: 100%; height: 38px; background: #f3f4f6; color: #6b7280; cursor: not-allowed;" value="<?php echo htmlspecialchars($current_admin['username']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">New Username</label>
                        <input type="text" id="new_username" name="new_username_val" required class="filter-control" style="width: 100%; height: 38px;" value="<?php echo htmlspecialchars($current_admin['username']); ?>" autocomplete="off">
                        <small style="font-size: 11px; color: var(--text-light); margin-top: 3px; display: block;">Leave as is if you do not wish to change your username.</small>
                    </div>
                </div>

                <!-- Right Column: Password Update -->
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 2px;">
                        <h3 style="font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700; color: var(--text-dark); margin: 0; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-key" style="color: var(--primary);"></i> Update Password</h3>
                    </div>

                    <div class="form-group">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">New Password <span style="font-weight: 400; text-transform: none; color: var(--text-light);">(optional)</span></label>
                        <div style="position: relative;">
                            <input type="password" id="new_password" name="new_pass_val" class="filter-control" style="width: 100%; padding-right: 40px; height: 38px;" placeholder="Leave blank to keep current..." autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                            <button type="button" onclick="togglePasswordVisibility('new_password', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-light); cursor: pointer; font-size: 16px;"><i class="fa-regular fa-eye-slash"></i></button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">Confirm New Password <span style="font-weight: 400; text-transform: none; color: var(--text-light);">(optional)</span></label>
                        <div style="position: relative;">
                            <input type="password" id="confirm_password" name="cfm_pass_val" class="filter-control" style="width: 100%; padding-right: 40px; height: 38px;" placeholder="Confirm new password..." autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                            <button type="button" onclick="togglePasswordVisibility('confirm_password', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-light); cursor: pointer; font-size: 16px;"><i class="fa-regular fa-eye-slash"></i></button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Verification & Action -->
            <div style="border-top: 1px solid var(--border-color); padding-top: 20px; margin-top: 5px; display: flex; flex-direction: column; align-items: center; gap: 15px;">
                <div class="form-group" style="width: 100%; max-width: 380px; text-align: center;">
                    <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">Confirm Identity: Enter Current Password</label>
                    <div style="position: relative;">
                        <input type="password" id="current_password" name="cur_pass_val" required class="filter-control" style="width: 100%; padding-right: 40px; height: 38px; text-align: center;" placeholder="Enter current password to save changes..." autocomplete="current-password" readonly onfocus="this.removeAttribute('readonly');">
                        <button type="button" onclick="togglePasswordVisibility('current_password', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-light); cursor: pointer; font-size: 16px;"><i class="fa-regular fa-eye-slash"></i></button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; max-width: 380px; height: 40px; justify-content: center; border-radius: 10px; font-size: 13px; font-weight: 600; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);"><i class="fa-solid fa-shield-check" style="margin-right: 6px;"></i> Save Security Settings</button>
            </div>
            
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
