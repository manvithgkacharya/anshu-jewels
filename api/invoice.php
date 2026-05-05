<?php
require_once __DIR__ . '/../config/config.php';

$orderId = $_GET['order_id'] ?? ($_GET['id'] ?? 0);

if (!$orderId) {
    die("Invalid Order ID");
}

try {
    $stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email 
                          FROM orders o 
                          LEFT JOIN users u ON o.user_id = u.id 
                          WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        die("Order not found");
    }

    // Security check: Must be logged in as the order owner OR as an admin
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $isOwner = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $order['user_id']);
    $isAdmin = isset($_SESSION['admin_id']);

    if (!$isOwner && !$isAdmin) {
        die("Unauthorized access to invoice");
    }

    $stmtParams = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmtParams->execute([$orderId]);
    $items = $stmtParams->fetchAll();
    
    // Output HTML and force download
    $filename = "Invoice-" . $order['order_number'] . ".html";
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?php echo $order['order_number']; ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; padding: 40px; margin: 0; background: #fff; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); font-size: 16px; line-height: 24px; color: #555; }
        .invoice-box table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
        .invoice-box table td { padding: 5px; vertical-align: top; }
        .invoice-box table tr td:nth-child(2) { text-align: right; }
        .invoice-box table tr.top table td { padding-bottom: 20px; }
        .invoice-box table tr.top table td.title { font-size: 45px; line-height: 45px; color: #333; }
        .invoice-box table tr.information table td { padding-bottom: 40px; }
        .invoice-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
        .invoice-box table tr.details td { padding-bottom: 20px; }
        .invoice-box table tr.item td { border-bottom: 1px solid #eee; }
        .invoice-box table tr.item.last td { border-bottom: none; }
        .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; }
        .store-name { font-weight: bold; color: #d97706; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table>
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">
                                <span class="store-name">Anshu Jewels</span>
                            </td>
                            <td>
                                Invoice #: <?php echo $order['order_number']; ?><br>
                                Date: <?php echo date('F d, Y', strtotime($order['created_at'])); ?><br>
                                Status: <?php echo ucfirst($order['payment_status']); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                <strong>Billed To:</strong><br>
                                <?php echo htmlspecialchars($order['shipping_name']); ?><br>
                                <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                                <?php echo htmlspecialchars($order['shipping_city']); ?>, <?php echo htmlspecialchars($order['shipping_state']); ?> <?php echo htmlspecialchars($order['shipping_pincode']); ?><br>
                                <?php echo htmlspecialchars($order['customer_email']); ?>
                            </td>
                            <td>
                                <strong>Anshu Jewels</strong><br>
                                Based in Ullala,Mangaluru<br>
                                Dakshina Kannada, Karnataka 575020<br>
                                support@anshujewels.com
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="heading">
                <td>Item</td>
                <td>Price</td>
            </tr>
            
            <?php foreach($items as $item): ?>
            <tr class="item">
                <td><?php echo htmlspecialchars($item['product_title']); ?> (x<?php echo $item['quantity']; ?>)</td>
                <td>₹<?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="item last">
                <td><br></td>
                <td><br></td>
            </tr>
            
            <tr class="details">
                <td>Subtotal</td>
                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
            </tr>
            <tr class="details">
                <td>Shipping</td>
                <td>₹<?php echo number_format($order['shipping_amount'], 2); ?></td>
            </tr>
            <tr class="details">
                <td>Tax</td>
                <td>₹<?php echo number_format($order['tax_amount'], 2); ?></td>
            </tr>
            <?php if ($order['discount_amount'] > 0): ?>
            <tr class="details">
                <td>Discount</td>
                <td>-₹<?php echo number_format($order['discount_amount'], 2); ?></td>
            </tr>
            <?php endif; ?>
            <tr class="total">
                <td></td>
                <td>Total: ₹<?php echo number_format($order['final_amount'], 2); ?></td>
            </tr>
            
            <tr class="details">
                <td colspan="2" style="text-align:center; padding-top: 50px;">
                    <p style="font-size: 14px; color: #777;">Thank you for shopping securely with Anshu Jewels!</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
<?php 
} catch (Exception $e) {
    die("Error generating invoice.");
}
?>
