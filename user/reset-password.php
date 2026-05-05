<?php
require_once __DIR__ . '/../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/user/profile.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        try {
            // Check if email exists
            $stmt = $db->prepare("SELECT id, email FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store token
                $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$email, $token, $expiresAt]);
                
                // In production, send email with reset link
                $resetLink = SITE_URL . "/user/reset-password.php?token=" . $token;
                
                // For development, show the link
                $success = "Password reset link has been sent to your email. <br><small>Dev mode: <a href='$resetLink'>Click here to reset</a></small>";
                
                // In production, uncomment this:
                // sendEmail($email, 'Password Reset', "Click here to reset your password: $resetLink");
                // $success = "Password reset link has been sent to your email.";
            } else {
                // Don't reveal if email exists or not for security
                $success = "If that email exists, a password reset link has been sent.";
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}

// Handle token-based reset
$token = $_GET['token'] ?? '';
$showResetForm = false;

if (!empty($token)) {
    try {
        $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $resetRecord = $stmt->fetch();
        
        if ($resetRecord) {
            $showResetForm = true;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if (empty($newPassword) || empty($confirmPassword)) {
                    $error = 'Please fill in all fields';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'Passwords do not match';
                } elseif (strlen($newPassword) < 6) {
                    $error = 'Password must be at least 6 characters';
                } else {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
                    $stmt->execute([$hashedPassword, $resetRecord['email']]);
                    
                    // Delete used token
                    $db->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);
                    
                    $success = 'Password reset successful! You can now login.';
                    $showResetForm = false;
                }
            }
        } else {
            $error = 'Invalid or expired reset token.';
        }
    } catch (PDOException $e) {
        $error = 'An error occurred. Please try again.';
    }
}

require_once __DIR__ . '/../includes/header.php';
renderHeader('Reset Password', 'Reset your password');
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
</style>

<div class="auth-container">
    <div class="container">
        <div class="auth-card" style="margin: 0 auto;">
            <div class="auth-header">
                <h1 class="auth-title">🔒 Reset Password</h1>
                <p class="auth-subtitle">
                    <?php echo $showResetForm ? 'Enter your new password' : 'Enter your email to receive a reset link'; ?>
                </p>
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
            
            <?php if ($showResetForm): ?>
                <!-- Reset Password Form -->
                <form method="POST" action="" data-validate>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-input" 
                               placeholder="••••••••" required minlength="6">
                        <span class="form-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                               placeholder="••••••••" required>
                        <span class="form-error"></span>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </form>
            <?php else: ?>
                <!-- Request Reset Form -->
                <form method="POST" action="" data-validate>
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" 
                               placeholder="you@example.com" required>
                        <span class="form-error"></span>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Send Reset Link
                    </button>
                </form>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: var(--space-6); padding-top: var(--space-6); border-top: 1px solid var(--border-color);">
                <p style="color: var(--text-secondary);">
                    Remember your password? 
                    <a href="<?php echo SITE_URL; ?>/user/login.php" style="font-weight: 600;">Login</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
renderFooter();
?>
