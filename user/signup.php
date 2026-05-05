<?php
require_once __DIR__ . '/../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/user/profile.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        try {
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Email already registered';
            } else {
                // Create new user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $hashedPassword]);
                
                $success = 'Account created successfully! You can now login.';
                
                // Auto login
                $_SESSION['user_id'] = $db->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                // Redirect after 2 seconds
                header("refresh:2;url=" . SITE_URL . "/user/index.php");
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
renderHeader('Sign Up', 'Create your account');
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

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
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
                <h1 class="auth-title">✨ Join Anshu Jewels</h1>
                <p class="auth-subtitle">Create your account and start shopping</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" data-validate>
                <?php generateCSRFToken(); ?>
                
                <div class="form-group">
                    <label class="form-label" for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-input" 
                           placeholder="John Doe" required 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    <span class="form-error"></span>
                </div>
                
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
                           placeholder="••••••••" required minlength="6">
                    <span class="form-error"></span>
                    <small style="color: var(--text-tertiary); font-size: var(--text-xs);">
                        At least 6 characters
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                           placeholder="••••••••" required>
                    <span class="form-error"></span>
                </div>
                
                <div class="mb-6">
                    <label style="display: flex; align-items: flex-start; gap: var(--space-2); cursor: pointer;">
                        <input type="checkbox" name="terms" required style="width: auto; margin-top: 4px;">
                        <span style="font-size: var(--text-sm); color: var(--text-secondary);">
                            I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                        </span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="divider">
                <span>or</span>
            </div>
            
            <p class="text-center" style="color: var(--text-secondary);">
                Already have an account? 
                <a href="<?php echo SITE_URL; ?>/user/login.php" style="font-weight: 600;">Login</a>
            </p>
        </div>
    </div>
</div>

<?php
renderFooter();
?>
