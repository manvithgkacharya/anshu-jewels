<?php
require_once __DIR__ . '/../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/user/profile.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                if (isset($_SESSION['redirect_after_login'])) {
                    $redirect_url = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    redirect($redirect_url);
                } else {
                    redirect(SITE_URL . '/user/index.php');
                }
            } else {
                // Check if it's an admin
                $stmt = $db->prepare("SELECT * FROM admin_users WHERE (username = ? OR email = ?) AND is_active = 1");
                $stmt->execute([$email, $email]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_role'] = $admin['role'];
                    
                    redirect(SITE_URL . '/admin/index.php');
                } else {
                    $error = 'Invalid email or password';
                }
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
renderHeader('Login', 'Login to your account');
?>

<style>
.auth-container {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-8) 0;
}

.auth-card {
    max-width: 450px;
    width: 100%;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-2xl);
    padding: var(--space-8);
    box-shadow: var(--shadow-xl);
}

.auth-header {
    text-align: center;
    margin-bottom: var(--space-8);
}

.auth-title {
    font-size: var(--text-3xl);
    font-weight: 800;
    margin-bottom: var(--space-2);
    background: linear-gradient(135deg, var(--gold-500), var(--gold-700));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.auth-subtitle {
    color: var(--text-secondary);
}

.alert {
    padding: var(--space-4);
    border-radius: var(--radius-lg);
    margin-bottom: var(--space-6);
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.divider {
    text-align: center;
    margin: var(--space-6) 0;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: var(--border-color);
}

.divider span {
    background: var(--bg-primary);
    padding: 0 var(--space-4);
    position: relative;
    color: var(--text-tertiary);
    font-size: var(--text-sm);
}
</style>

<div class="auth-container">
    <div class="container">
        <div class="auth-card" style="margin: 0 auto;">
            <div class="auth-header">
                <h1 class="auth-title">✨ Welcome Back</h1>
                <p class="auth-subtitle">Login to your Anshu Jewels account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" data-validate>
                <?php generateCSRFToken(); ?>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           placeholder="you@example.com" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    <span class="form-error"></span>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="••••••••" required>
                    <span class="form-error"></span>
                </div>
                
                <div class="flex justify-between items-center mb-6">
                    <label style="display: flex; align-items: center; gap: var(--space-2); cursor: pointer;">
                        <input type="checkbox" name="remember" style="width: auto;">
                        <span style="font-size: var(--text-sm);">Remember me</span>
                    </label>
                    <a href="<?php echo SITE_URL; ?>/user/reset-password.php" style="font-size: var(--text-sm);">
                        Forgot password?
                    </a>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="divider">
                <span>or</span>
            </div>
            
            <p class="text-center" style="color: var(--text-secondary);">
                Don't have an account? 
                <a href="<?php echo SITE_URL; ?>/user/signup.php" style="font-weight: 600;">Sign up</a>
            </p>
        </div>
    </div>
</div>

<?php
renderFooter();
?>
