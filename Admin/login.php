<?php
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../db_connect.php';

    $username = isset($_POST['ad_user_login']) ? trim($_POST['ad_user_login']) : '';
    $password = isset($_POST['ad_pass_login']) ? $_POST['ad_pass_login'] : '';

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $user['username'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (\PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    } else {
        $error = 'Please fill in both fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Adver Digify</title>
    <link rel="icon" type="image/png" href="./assets/favicon.png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #f7f6f2; /* Cream page background */
            --card-bg: #ffffff; /* Clean white card */
            --border-color: #e5e3dd; /* Soft cream-border */
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --text-color: #1f2937;
            --text-muted: #6b7280;
            --error-bg: #fef2f2;
            --error-border: #fecaca;
            --error-text: #991b1b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-color);
            overflow: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 24px;
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 0px; /* Square type card */
            padding: 45px 35px;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.08);
            text-align: center;
            position: relative;
        }

        .logo-container {
            margin-bottom: 25px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .logo-text {
            font-family: 'Outfit', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .login-heading {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.8px;
            color: #111827;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4b5563;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            height: 45px;
            padding: 10px 16px;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            color: var(--text-color);
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease-in-out;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .password-toggle-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle-btn:hover {
            color: var(--primary-color);
        }

        .btn-login {
            width: 100%;
            height: 46px;
            background: var(--primary-color);
            border: none;
            border-radius: 12px;
            color: white;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
            margin-top: 10px;
        }

        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.2);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }

        .error-message i {
            font-size: 16px;
        }

        .footer-note {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 30px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="logo-container">
            <h1 class="logo-text">Adver Digify</h1>
            <h2 class="login-heading">ADMIN LOGIN</h2>
            <div class="subtitle">Leads Administration Dashboard</div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <!-- Dummy inputs to catch browser autofill -->
            <input type="text" name="username" style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;" tabindex="-1" autocomplete="off">
            <input type="password" name="password" style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;" tabindex="-1" autocomplete="off">

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="ad_user_login" class="form-control" placeholder="Enter username" required autocomplete="new-username" readonly onfocus="this.removeAttribute('readonly');">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="ad_pass_login" class="form-control" placeholder="Enter password" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', this)">
                        <i class="fa-regular fa-eye-slash"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="footer-note">
            © 2026 Adver Digify. Secure Portal.
        </div>
    </div>
</div>

<script>
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
</script>

</body>
</html>
