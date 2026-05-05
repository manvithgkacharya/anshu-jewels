<?php
/**
 * Email Notification System
 * Handles all email notifications using SMTP
 */

require_once __DIR__ . '/../config/config.php';

class EmailNotification {
    private $db;
    private $settings;
    
    public function __construct($database) {
        $this->db = $database;
        $this->loadSettings();
    }
    
    private function loadSettings() {
        try {
            $stmt = $this->db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'smtp_%' OR setting_key LIKE 'from_%'");
            $this->settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            $this->settings = [];
        }
    }
    
    /**
     * Send email using SMTP
     */
    public function send($to, $subject, $body, $isHtml = true) {
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = !empty($this->settings['smtp_host']) ? $this->settings['smtp_host'] : 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = !empty($this->settings['smtp_username']) ? $this->settings['smtp_username'] : ''; 
            $mail->Password   = !empty($this->settings['smtp_password']) ? $this->settings['smtp_password'] : ''; 
            
            $encryption = !empty($this->settings['smtp_encryption']) ? $this->settings['smtp_encryption'] : 'tls';
            $mail->SMTPSecure = ($encryption === 'ssl') ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = !empty($this->settings['smtp_port']) ? $this->settings['smtp_port'] : 587;

            // Recipients
            $fromEmail = !empty($this->settings['from_email']) ? $this->settings['from_email'] : 'noreply@anshujewels.com';
            $fromName = !empty($this->settings['from_name']) ? $this->settings['from_name'] : 'Anshu Jewels';
            
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            return $mail->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($orderId) {
        try {
            $stmt = $this->db->prepare("SELECT o.*, u.name, u.email 
                                        FROM orders o 
                                        JOIN users u ON o.user_id = u.id 
                                        WHERE o.id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if (!$order) return false;
            
            $subject = "Order Confirmation - #" . $order['order_number'];
            $body = $this->getOrderConfirmationTemplate($order);
            
            return $this->send($order['email'], $subject, $body);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordReset($email, $token) {
        $resetLink = SITE_URL . "/user/reset-password.php?token=" . $token;
        
        $subject = "Password Reset Request";
        $body = $this->getPasswordResetTemplate($resetLink);
        
        return $this->send($email, $subject, $body);
    }
    
    /**
     * Send order status update email
     */
    public function sendOrderStatusUpdate($orderId, $newStatus) {
        try {
            $stmt = $this->db->prepare("SELECT o.*, u.name, u.email 
                                        FROM orders o 
                                        JOIN users u ON o.user_id = u.id 
                                        WHERE o.id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if (!$order) return false;
            
            $subject = "Order Status Update - #" . $order['order_number'];
            $body = $this->getOrderStatusTemplate($order, $newStatus);
            
            return $this->send($order['email'], $subject, $body);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Send welcome email
     */
    public function sendWelcomeEmail($userId) {
        try {
            $stmt = $this->db->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) return false;
            
            $subject = "Welcome to Anshu Jewels!";
            $body = $this->getWelcomeTemplate($user['name']);
            
            return $this->send($user['email'], $subject, $body);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Email Templates
    
    private function getOrderConfirmationTemplate($order) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; }
                .order-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .footer { background: #1f2937; color: #9ca3af; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✨ Order Confirmed!</h1>
                    <p>Thank you for your order</p>
                </div>
                <div class='content'>
                    <p>Hi {$order['shipping_name']},</p>
                    <p>Your order has been confirmed and is being processed.</p>
                    
                    <div class='order-details'>
                        <h3>Order Details</h3>
                        <p><strong>Order Number:</strong> {$order['order_number']}</p>
                        <p><strong>Order Date:</strong> " . date('F d, Y', strtotime($order['created_at'])) . "</p>
                        <p><strong>Total Amount:</strong> ₹" . number_format($order['final_amount'], 2) . "</p>
                        <p><strong>Payment Method:</strong> " . ucfirst($order['payment_method']) . "</p>
                    </div>
                    
                    <p><strong>Shipping Address:</strong><br>
                    {$order['shipping_address']}<br>
                    {$order['shipping_city']}, {$order['shipping_state']} - {$order['shipping_pincode']}</p>
                    
                    <a href='" . SITE_URL . "/user/orders.php' class='button'>View Order Details</a>
                </div>
                <div class='footer'>
                    <p>© 2024 Anshu Jewels. All rights reserved.</p>
                    <p>Handmade Jewelry with Love</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getPasswordResetTemplate($resetLink) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; }
                .button { display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .footer { background: #1f2937; color: #9ca3af; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔒 Password Reset</h1>
                </div>
                <div class='content'>
                    <p>You requested to reset your password.</p>
                    <p>Click the button below to reset your password. This link will expire in 1 hour.</p>
                    <a href='{$resetLink}' class='button'>Reset Password</a>
                    <p><small>If you didn't request this, please ignore this email.</small></p>
                </div>
                <div class='footer'>
                    <p>© 2024 Anshu Jewels. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getOrderStatusTemplate($order, $status) {
        $statusMessages = [
            'processing' => 'Your order is being processed',
            'shipped' => 'Your order has been shipped!',
            'delivered' => 'Your order has been delivered',
            'cancelled' => 'Your order has been cancelled'
        ];
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; }
                .button { display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .footer { background: #1f2937; color: #9ca3af; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📦 Order Update</h1>
                </div>
                <div class='content'>
                    <p>Hi {$order['shipping_name']},</p>
                    <p><strong>" . ($statusMessages[$status] ?? 'Your order status has been updated') . "</strong></p>
                    <p>Order Number: <strong>{$order['order_number']}</strong></p>
                    <a href='" . SITE_URL . "/user/orders.php' class='button'>Track Order</a>
                </div>
                <div class='footer'>
                    <p>© 2024 Anshu Jewels. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getWelcomeTemplate($name) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; }
                .button { display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .footer { background: #1f2937; color: #9ca3af; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✨ Welcome to Anshu Jewels!</h1>
                </div>
                <div class='content'>
                    <p>Hi {$name},</p>
                    <p>Thank you for joining Anshu Jewels! We're excited to have you as part of our community.</p>
                    <p>Explore our collection of handmade jewelry crafted with love and care.</p>
                    <a href='" . SITE_URL . "/user/products.php' class='button'>Start Shopping</a>
                </div>
                <div class='footer'>
                    <p>© 2024 Anshu Jewels. All rights reserved.</p>
                    <p>Handmade Jewelry with Love</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

// Usage example:
// $emailService = new EmailNotification($db);
// $emailService->sendOrderConfirmation($orderId);
// $emailService->sendPasswordReset($email, $token);
// $emailService->sendWelcomeEmail($userId);
?>
