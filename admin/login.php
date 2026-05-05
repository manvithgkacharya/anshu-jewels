<?php
require_once __DIR__ . '/../config/config.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE (username = ? OR email = ?) AND is_active = 1");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                
                redirect(SITE_URL . '/admin/index.php');
            } else {
                $error = 'Invalid credentials';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Anshu Jewels</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e1e2d 0%, #0a0a0f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .admin-login-card {
            max-width: 450px;
            width: 100%;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-2xl);
            padding: var(--space-8);
            box-shadow: var(--shadow-2xl);
        }
        
        .admin-header {
            text-align: center;
            margin-bottom: var(--space-8);
        }
        
        .admin-logo {
            margin-bottom: var(--space-4);
            text-align: center;
        }
        
        .admin-title {
            font-size: var(--text-2xl);
            font-weight: 800;
            margin-bottom: var(--space-2);
        }
        
        .alert-error {
            padding: var(--space-4);
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-6);
        }
    </style>
</head>
<body>
    <div class="admin-login-card">
        <div class="admin-header">
            <div class="admin-logo">
                <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="Anshu Jewels" style="max-width: 350px; height: auto;">
            </div>
            <h1 class="admin-title">Admin Panel</h1>
            <p style="color: var(--text-secondary);">Anshu Jewels Management</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="username">Username or Email</label>
                <input type="text" id="username" name="username" class="form-input" 
                       placeholder="admin" required autofocus>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" 
                       placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-sign-in-alt"></i> Login to Dashboard
            </button>
        </form>
        
        <div style="text-align: center; margin-top: var(--space-6); padding-top: var(--space-6); border-top: 1px solid var(--border-color);">
            <a href="<?php echo SITE_URL; ?>/user/index.php" style="color: var(--text-secondary); font-size: var(--text-sm);">
                <i class="fas fa-arrow-left"></i> Back to Website
            </a>
        </div>
    </div>
</body>
</html>
