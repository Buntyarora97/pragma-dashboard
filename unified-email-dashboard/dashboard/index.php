<?php
/**
 * Unified Email Dashboard - Main Dashboard
 */

require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Get dashboard statistics
$stats = getDashboardStats();

// Get recent emails
$recentEmails = getEmails([], 1, 10);

// Get all email accounts
$accounts = getEmailAccounts();

// Get flash message
$flashMessage = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Unified Email Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <i class="bi bi-envelope-fill"></i>
                <span>Unified Email</span>
            </div>
            
            <ul class="list-unstyled components">
                <li class="active">
                    <a href="/dashboard/">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="/email/accounts.php">
                        <i class="bi bi-envelope-at"></i>
                        <span>Email Accounts</span>
                    </a>
                </li>
                <li>
                    <a href="/email/inbox.php">
                        <i class="bi bi-inbox"></i>
                        <span>Unified Inbox</span>
                        <?php if ($stats['unread_emails'] > 0): ?>
                        <span class="badge bg-danger"><?php echo $stats['unread_emails']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="/email/compose.php">
                        <i class="bi bi-pencil-square"></i>
                        <span>Compose Email</span>
                    </a>
                </li>
                <li>
                    <a href="/email/sync.php">
                        <i class="bi bi-arrow-repeat"></i>
                        <span>Sync Emails</span>
                    </a>
                </li>
            </ul>
            
            <ul class="list-unstyled components mt-auto">
                <li>
                    <a href="/auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Page Content -->
        <div id="content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <span class="navbar-brand ms-3">Dashboard</span>
                    
                    <div class="ms-auto d-flex align-items-center">
                        <span class="me-3 text-muted">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
                        </span>
                    </div>
                </div>
            </nav>
            
            <!-- Main Content -->
            <div class="container-fluid p-4">
                <?php if ($flashMessage): ?>
                <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($flashMessage['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Email Accounts</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_accounts']; ?></h2>
                                    </div>
                                    <i class="bi bi-envelope-at stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Emails</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_emails']; ?></h2>
                                    </div>
                                    <i class="bi bi-envelope stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Unread Emails</h6>
                                        <h2 class="mb-0"><?php echo $stats['unread_emails']; ?></h2>
                                    </div>
                                    <i class="bi bi-envelope-open stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Recent (7 days)</h6>
                                        <h2 class="mb-0"><?php echo $stats['recent_emails']; ?></h2>
                                    </div>
                                    <i class="bi bi-calendar-check stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <a href="/email/compose.php" class="btn btn-primary w-100 mb-2">
                                            <i class="bi bi-pencil-square me-2"></i>Compose Email
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/email/accounts.php?action=add" class="btn btn-success w-100 mb-2">
                                            <i class="bi bi-plus-circle me-2"></i>Add Account
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/email/sync.php" class="btn btn-info w-100 mb-2">
                                            <i class="bi bi-arrow-repeat me-2"></i>Sync All Emails
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/email/inbox.php" class="btn btn-secondary w-100 mb-2">
                                            <i class="bi bi-inbox me-2"></i>View Inbox
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Email Accounts & Recent Emails -->
                <div class="row">
                    <!-- Email Accounts -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-envelope-at me-2"></i>Email Accounts</h5>
                                <a href="/email/accounts.php" class="btn btn-sm btn-primary">Manage</a>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($accounts)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox display-4"></i>
                                    <p class="mt-2">No email accounts added yet.</p>
                                    <a href="/email/accounts.php?action=add" class="btn btn-primary btn-sm">Add Account</a>
                                </div>
                                <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($accounts as $account): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($account['display_name'] ?: $account['email']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($account['email']); ?></small>
                                        </div>
                                        <span class="badge bg-<?php echo $account['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $account['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Emails -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Emails</h5>
                                <a href="/email/inbox.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($recentEmails['emails'])): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-envelope display-4"></i>
                                    <p class="mt-2">No emails found. Sync your accounts to fetch emails.</p>
                                    <a href="/email/sync.php" class="btn btn-primary btn-sm">Sync Now</a>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>From</th>
                                                <th>Subject</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentEmails['emails'] as $email): ?>
                                            <tr class="<?php echo $email['is_read'] ? '' : 'table-active fw-bold'; ?>">
                                                <td>
                                                    <?php echo htmlspecialchars($email['sender_name'] ?: $email['sender_email']); ?>
                                                </td>
                                                <td>
                                                    <a href="/email/read.php?id=<?php echo $email['id']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars(truncateText($email['subject'], 40)); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo formatDate($email['date_received'], 'M d, H:i'); ?></td>
                                                <td>
                                                    <?php if (!$email['is_read']): ?>
                                                    <span class="badge bg-primary">Unread</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-secondary">Read</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>
