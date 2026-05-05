<?php
require_once __DIR__ . '/../config/config.php';

// Check admin authentication
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
}

$error = '';
$success = '';

// Handle ticket status update
if (isset($_POST['update_status'])) {
    $ticketId = (int)$_POST['ticket_id'];
    $status = sanitize($_POST['status']);
    
    try {
        $stmt = $db->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
        $stmt->execute([$status, $ticketId]);
        $success = 'Ticket status updated!';
    } catch (PDOException $e) {
        $error = 'Failed to update ticket';
    }
}

// Handle admin reply
if (isset($_POST['add_reply'])) {
    $ticketId = (int)$_POST['ticket_id'];
    $reply = sanitize($_POST['reply']);
    
    try {
        $stmt = $db->prepare("UPDATE support_tickets SET admin_reply = ?, status = 'answered', replied_at = NOW() WHERE id = ?");
        $stmt->execute([$reply, $ticketId]);
        $success = 'Reply sent successfully!';
    } catch (PDOException $e) {
        $error = 'Failed to send reply';
    }
}

// Fetch all tickets
$statusFilter = $_GET['status'] ?? 'all';

try {
    $query = "SELECT st.*, u.name as user_name, u.email as user_email 
              FROM support_tickets st
              LEFT JOIN users u ON st.user_id = u.id
              WHERE 1=1";
    
    if ($statusFilter !== 'all') {
        $query .= " AND st.status = ?";
        $params = [$statusFilter];
    } else {
        $params = [];
    }
    
    $query .= " ORDER BY st.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    $tickets = [];
}

// View ticket details
$viewTicket = null;
if (isset($_GET['view'])) {
    $viewId = (int)$_GET['view'];
    try {
        $stmt = $db->prepare("SELECT st.*, u.name as user_name, u.email as user_email, u.phone as user_phone
                              FROM support_tickets st
                              LEFT JOIN users u ON st.user_id = u.id
                              WHERE st.id = ?");
        $stmt->execute([$viewId]);
        $viewTicket = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Failed to load ticket';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Anshu Jewels Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg-secondary); }
        .admin-layout { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .admin-sidebar { background: var(--bg-primary); border-right: 1px solid var(--border-color); padding: var(--space-6); }
        .admin-logo {
            margin-bottom: var(--space-8);
            text-align: center;
        }
        .admin-menu { list-style: none; }
        .admin-menu-item { margin-bottom: var(--space-2); }
        .admin-menu-link { display: flex; align-items: center; gap: var(--space-3); padding: var(--space-3); color: var(--text-secondary); text-decoration: none; border-radius: var(--radius-md); transition: all var(--transition-fast); }
        .admin-menu-link:hover, .admin-menu-link.active { background: var(--bg-secondary); color: var(--accent-color); }
        .admin-content { padding: var(--space-8); }
        .filters-bar { display: flex; gap: var(--space-4); margin-bottom: var(--space-6); }
        .ticket-card { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: var(--space-6); margin-bottom: var(--space-4); cursor: pointer; transition: all var(--transition-base); }
        .ticket-card:hover { box-shadow: var(--shadow-lg); }
        .ticket-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--space-3); }
        .status-open { background: #fef3c7; color: #92400e; }
        .status-answered { background: #d1fae5; color: #065f46; }
        .status-closed { background: #e5e7eb; color: #374151; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center; overflow-y: auto; padding: var(--space-8); }
        .modal.active { display: flex; }
        .modal-content { background: var(--bg-primary); border-radius: var(--radius-xl); padding: var(--space-8); max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="Anshu Jewels" style="max-width: 200px; height: auto;">
            </div>
            <ul class="admin-menu">
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/index.php" class="admin-menu-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/products.php" class="admin-menu-link"><i class="fas fa-gem"></i> Products</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/orders.php" class="admin-menu-link"><i class="fas fa-shopping-bag"></i> Orders</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/users.php" class="admin-menu-link"><i class="fas fa-users"></i> Users</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/coupons.php" class="admin-menu-link"><i class="fas fa-ticket-alt"></i> Coupons</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/reports.php" class="admin-menu-link"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/support.php" class="admin-menu-link active"><i class="fas fa-headset"></i> Support</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/settings.php" class="admin-menu-link"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="admin-menu-item" style="margin-top: var(--space-8);"><a href="<?php echo SITE_URL; ?>/user/index.php" class="admin-menu-link"><i class="fas fa-globe"></i> View Website</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/api/logout.php?admin=1" class="admin-menu-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-8);">
                <div>
                    <h1 style="font-size: var(--text-4xl); font-weight: 800; margin-bottom: var(--space-2);">Support Tickets</h1>
                    <p style="color: var(--text-secondary);">Manage customer support requests</p>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: var(--space-6);">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom: var(--space-6);">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="filters-bar">
                <a href="?status=all" class="btn <?php echo $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
                    All Tickets
                </a>
                <a href="?status=open" class="btn <?php echo $statusFilter === 'open' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Open
                </a>
                <a href="?status=answered" class="btn <?php echo $statusFilter === 'answered' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Answered
                </a>
                <a href="?status=closed" class="btn <?php echo $statusFilter === 'closed' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Closed
                </a>
            </div>
            
            <!-- Tickets List -->
            <?php if (empty($tickets)): ?>
                <div style="text-align: center; padding: var(--space-16); background: var(--bg-primary); border-radius: var(--radius-xl);">
                    <i class="fas fa-inbox" style="font-size: 4rem; color: var(--text-tertiary); margin-bottom: var(--space-4);"></i>
                    <h3>No tickets found</h3>
                    <p style="color: var(--text-secondary);">All support tickets will appear here</p>
                </div>
            <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-card" onclick="window.location.href='?view=<?php echo $ticket['id']; ?>'">
                        <div class="ticket-header">
                            <div>
                                <h3 style="font-size: var(--text-lg); font-weight: 600; margin-bottom: var(--space-2);">
                                    <?php echo htmlspecialchars($ticket['subject']); ?>
                                </h3>
                                <p style="color: var(--text-secondary); font-size: var(--text-sm);">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($ticket['user_name']); ?> 
                                    • <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?>
                                </p>
                            </div>
                            <span class="badge status-<?php echo $ticket['status']; ?>">
                                <?php echo ucfirst($ticket['status']); ?>
                            </span>
                        </div>
                        <p style="color: var(--text-secondary);">
                            <?php echo htmlspecialchars(substr($ticket['message'], 0, 150)); ?>...
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Ticket Details Modal -->
    <?php if ($viewTicket): ?>
    <div class="modal active">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); padding-bottom: var(--space-4); border-bottom: 2px solid var(--border-color);">
                <h2 style="font-size: var(--text-2xl); font-weight: 700;"><?php echo htmlspecialchars($viewTicket['subject']); ?></h2>
                <button onclick="window.location.href='/admin/support.php'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Customer Info -->
            <div style="background: var(--bg-secondary); padding: var(--space-4); border-radius: var(--radius-lg); margin-bottom: var(--space-6);">
                <strong>Customer:</strong> <?php echo htmlspecialchars($viewTicket['user_name']); ?><br>
                <strong>Email:</strong> <a href="mailto:<?php echo $viewTicket['user_email']; ?>"><?php echo htmlspecialchars($viewTicket['user_email']); ?></a><br>
                <strong>Date:</strong> <?php echo date('F d, Y H:i', strtotime($viewTicket['created_at'])); ?>
            </div>
            
            <!-- Message -->
            <div style="background: var(--bg-secondary); padding: var(--space-6); border-radius: var(--radius-lg); margin-bottom: var(--space-6);">
                <h4 style="margin-bottom: var(--space-3);">Customer Message:</h4>
                <p style="line-height: 1.6;"><?php echo nl2br(htmlspecialchars($viewTicket['message'])); ?></p>
            </div>
            
            <!-- Admin Reply -->
            <?php if ($viewTicket['admin_reply']): ?>
                <div style="background: #d1fae5; padding: var(--space-6); border-radius: var(--radius-lg); margin-bottom: var(--space-6);">
                    <h4 style="margin-bottom: var(--space-3); color: #065f46);">Your Reply:</h4>
                    <p style="line-height: 1.6; color: #065f46;"><?php echo nl2br(htmlspecialchars($viewTicket['admin_reply'])); ?></p>
                    <small style="color: #047857;">Replied on: <?php echo date('M d, Y H:i', strtotime($viewTicket['replied_at'])); ?></small>
                </div>
            <?php endif; ?>
            
            <!-- Reply Form -->
            <?php if ($viewTicket['status'] !== 'closed'): ?>
                <form method="POST" action="">
                    <input type="hidden" name="ticket_id" value="<?php echo $viewTicket['id']; ?>">
                    <input type="hidden" name="add_reply" value="1">
                    
                    <div class="form-group">
                        <label class="form-label">Your Reply</label>
                        <textarea name="reply" class="form-textarea" rows="6" required 
                                  placeholder="Type your response here..."><?php echo htmlspecialchars($viewTicket['admin_reply'] ?? ''); ?></textarea>
                    </div>
                    
                    <div style="display: flex; gap: var(--space-3);">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-paper-plane"></i> Send Reply
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Status Update -->
            <form method="POST" action="" style="margin-top: var(--space-6); padding-top: var(--space-6); border-top: 1px solid var(--border-color);">
                <input type="hidden" name="ticket_id" value="<?php echo $viewTicket['id']; ?>">
                <input type="hidden" name="update_status" value="1">
                
                <div style="display: flex; gap: var(--space-3); align-items: end;">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Update Status</label>
                        <select name="status" class="form-select">
                            <option value="open" <?php echo $viewTicket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="answered" <?php echo $viewTicket['status'] === 'answered' ? 'selected' : ''; ?>>Answered</option>
                            <option value="closed" <?php echo $viewTicket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-check"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="<?php echo JS_URL; ?>main.js"></script>
</body>
</html>
