<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/email-notification.php';

renderHeader('Contact Us', 'Get in touch with Anshu Jewels');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Save to support_tickets table
        try {
            $stmt = $db->prepare("INSERT INTO support_tickets (user_id, name, email, subject, message, status) VALUES (?, ?, ?, ?, ?, 'open')");
            $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
            $stmt->execute([$userId, $name, $email, $subject, $message]);
        } catch (PDOException $e) {
            // Silently fail if table doesn't exist
        }

        // Send Email Notification
        $emailService = new EmailNotification($db);
        
        // Get admin email from settings
        try {
            $stmt = $db->query("SELECT setting_value FROM site_settings WHERE setting_key = 'site_email'");
            $adminEmail = $stmt->fetchColumn();
            if (!$adminEmail) {
                $adminEmail = 'info@anshujewels.com';
            }
        } catch (PDOException $e) {
            $adminEmail = 'info@anshujewels.com';
        }

        // 1. Send to Admin
        $adminSubject = "New Contact Message: " . $subject;
        $adminBody = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <h2>New Contact Form Submission</h2>
            <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
            <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
            <p><strong>Message:</strong></p>
            <p style='background: #f9fafb; padding: 15px; border-radius: 5px;'>" . nl2br(htmlspecialchars($message)) . "</p>
        </body>
        </html>";
        $emailService->send($adminEmail, $adminSubject, $adminBody);

        // 2. Auto-reply to User
        $userSubject = "Thank you for contacting Anshu Jewels";
        $userBody = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <h2>Thank You!</h2>
            <p>Hi " . htmlspecialchars($name) . ",</p>
            <p>We have received your message regarding \"<strong>" . htmlspecialchars($subject) . "</strong>\" and will get back to you as soon as possible.</p>
            <p>Best regards,<br>Anshu Jewels Team</p>
        </body>
        </html>";
        $emailService->send($email, $userSubject, $userBody);

        $success = 'Thank you for contacting us! We will get back to you soon.';
    }
}
?>

<div class="container" style="padding: var(--space-16) var(--space-4); max-width: 800px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: var(--space-12);">
        <h1 style="font-size: var(--text-5xl); font-weight: 800; margin-bottom: var(--space-4);">
            Get in Touch
        </h1>
        <p style="font-size: var(--text-xl); color: var(--text-secondary);">
            We'd love to hear from you! Send us a message and we'll respond as soon as possible.
        </p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom: var(--space-6);">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom: var(--space-6);">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-2" style="gap: var(--space-8); margin-bottom: var(--space-12);">
        <div class="card">
            <div style="font-size: 2rem; margin-bottom: var(--space-4);">📧</div>
            <h3 style="margin-bottom: var(--space-2);">Email</h3>
            <p style="color: var(--text-secondary);">theanshujewels@gmail.com</p>
        </div>

        <div class="card">
            <div style="font-size: 2rem; margin-bottom: var(--space-4);">📱</div>
            <h3 style="margin-bottom: var(--space-2);">Phone</h3>
            <p style="color: var(--text-secondary);">+91 98765 43210</p>
        </div>
    </div>

    <div class="card" style="padding: var(--space-8);">
        <h2 style="font-size: var(--text-2xl); font-weight: 700; margin-bottom: var(--space-6);">
            Send us a Message
        </h2>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Your Name *</label>
                <input type="text" name="name" class="form-input" required
                    value="<?php echo isLoggedIn() ? htmlspecialchars($_SESSION['user_name'] ?? '') : ''; ?>"
                    placeholder="John Doe">
            </div>

            <div class="form-group">
                <label class="form-label">Your Email *</label>
                <input type="email" name="email" class="form-input" required
                    value="<?php echo isLoggedIn() ? htmlspecialchars($_SESSION['user_email'] ?? '') : ''; ?>"
                    placeholder="john@example.com">
            </div>

            <div class="form-group">
                <label class="form-label">Subject *</label>
                <input type="text" name="subject" class="form-input" required placeholder="How can we help you?">
            </div>

            <div class="form-group">
                <label class="form-label">Message *</label>
                <textarea name="message" class="form-textarea" rows="6" required
                    placeholder="Tell us more about your inquiry..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                <i class="fas fa-paper-plane"></i> Send Message
            </button>
        </form>
    </div>

    <div
        style="text-align: center; margin-top: var(--space-12); padding: var(--space-8); background: var(--bg-secondary); border-radius: var(--radius-xl);">
        <h3 style="margin-bottom: var(--space-4);">Visit Our Store</h3>
        <p style="color: var(--text-secondary); margin-bottom: var(--space-4);">
            Based in Ullala,Mangaluru<br>
            Dakshina Kannada, Karnataka 575020<br>
            India
        </p>
        <p style="color: var(--text-secondary);">
            <strong>Business Hours:</strong><br>
            Monday - Saturday: 10:00 AM - 8:00 PM<br>
            Sunday: 11:00 AM - 6:00 PM
        </p>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/header.php';
renderFooter();
?>