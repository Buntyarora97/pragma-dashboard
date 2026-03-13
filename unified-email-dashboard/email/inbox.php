<?php
/**
 * Unified Email Dashboard - Unified Inbox
 */

require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Get filters
$filters = [
    'account_id' => intval($_GET['account_id'] ?? 0),
    'search' => sanitize($_GET['search'] ?? ''),
    'is_read' => $_GET['is_read'] ?? ''
];

if ($filters['is_read'] !== '') {
    $filters['is_read'] = intval($filters['is_read']);
} else {
    unset($filters['is_read']);
}

// Pagination
$page = intval($_GET['page'] ?? 1);
$perPage = 25;

// Get emails
$emailData = getEmails($filters, $page, $perPage);

// Get all accounts for filter
$accounts = getEmailAccounts();

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verifyCSRFToken($csrfToken)) {
        $selectedEmails = $_POST['selected_emails'] ?? [];
        $action = $_POST['bulk_action'];
        
        if (!empty($selectedEmails)) {
            try {
                $db = getDBConnection();
                $ids = array_map('intval', $selectedEmails);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                
                switch ($action) {
                    case 'mark_read':
                        $stmt = $db->prepare("UPDATE emails SET is_read = 1 WHERE id IN ($placeholders)");
                        $stmt->execute($ids);
                        setFlashMessage('success', count($ids) . ' emails marked as read.');
                        break;
                    case 'mark_unread':
                        $stmt = $db->prepare("UPDATE emails SET is_read = 0 WHERE id IN ($placeholders)");
                        $stmt->execute($ids);
                        setFlashMessage('success', count($ids) . ' emails marked as unread.');
                        break;
                    case 'delete':
                        $stmt = $db->prepare("DELETE FROM emails WHERE id IN ($placeholders)");
                        $stmt->execute($ids);
                        setFlashMessage('success', count($ids) . ' emails deleted.');
                        break;
                }
                
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit();
            } catch (PDOException $e) {
                error_log("Bulk action error: " . $e->getMessage());
            }
        }
    }
}

$csrfToken = generateCSRFToken();
$flashMessage = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unified Inbox - Unified Email Dashboard</title>
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
                <li>
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
                <li class="active">
                    <a href="/email/inbox.php">
                        <i class="bi bi-inbox"></i>
                        <span>Unified Inbox</span>
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
                    
                    <span class="navbar-brand ms-3">Unified Inbox</span>
                    
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
                
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Search emails...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Email Account</label>
                                <select class="form-select" name="account_id">
                                    <option value="0">All Accounts</option>
                                    <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>" <?php echo $filters['account_id'] == $account['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($account['display_name'] ?: $account['email']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="is_read">
                                    <option value="">All</option>
                                    <option value="0" <?php echo isset($filters['is_read']) && $filters['is_read'] === 0 ? 'selected' : ''; ?>>Unread</option>
                                    <option value="1" <?php echo isset($filters['is_read']) && $filters['is_read'] === 1 ? 'selected' : ''; ?>>Read</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-funnel me-2"></i>Filter
                                </button>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <a href="/email/inbox.php" class="btn btn-secondary w-100">
                                    <i class="bi bi-x-circle me-2"></i>Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Email List -->
                <form method="POST" action="" id="bulkActionForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" class="form-check-input" id="selectAll" title="Select All">
                                <select class="form-select form-select-sm" name="bulk_action" style="width: auto;" onchange="if(this.value) document.getElementById('bulkActionForm').submit();">
                                    <option value="">Bulk Actions...</option>
                                    <option value="mark_read">Mark as Read</option>
                                    <option value="mark_unread">Mark as Unread</option>
                                    <option value="delete">Delete</option>
                                </select>
                            </div>
                            <div>
                                <span class="text-muted">
                                    Showing <?php echo (($page - 1) * $perPage) + 1; ?> - <?php echo min($page * $perPage, $emailData['total']); ?> of <?php echo $emailData['total']; ?> emails
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($emailData['emails'])): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-1"></i>
                                <h4 class="mt-3">No Emails Found</h4>
                                <p class="mb-3">
                                    <?php if (!empty($filters['search']) || !empty($filters['account_id'])): ?>
                                    Try adjusting your filters.
                                    <?php else: ?>
                                    Sync your email accounts to fetch emails.
                                    <?php endif; ?>
                                </p>
                                <?php if (empty($filters['search']) && empty($filters['account_id'])): ?>
                                <a href="/email/sync.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-repeat me-2"></i>Sync Now
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 email-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40px;"></th>
                                            <th style="width: 50px;"></th>
                                            <th>From</th>
                                            <th>Subject</th>
                                            <th>Account</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($emailData['emails'] as $email): ?>
                                        <tr class="<?php echo $email['is_read'] ? '' : 'table-active fw-bold'; ?>">
                                            <td>
                                                <input type="checkbox" class="form-check-input email-checkbox" name="selected_emails[]" value="<?php echo $email['id']; ?>">
                                            </td>
                                            <td>
                                                <?php if (!$email['is_read']): ?>
                                                <i class="bi bi-envelope-fill text-primary"></i>
                                                <?php else: ?>
                                                <i class="bi bi-envelope-open text-muted"></i>
                                                <?php endif; ?>
                                                <?php if ($email['attachments_count'] > 0): ?>
                                                <i class="bi bi-paperclip text-muted ms-1" title="<?php echo $email['attachments_count']; ?> attachment(s)"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($email['sender_name'] ?: $email['sender_email']); ?>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($email['sender_email']); ?></small>
                                            </td>
                                            <td>
                                                <a href="/email/read.php?id=<?php echo $email['id']; ?>" class="text-decoration-none <?php echo $email['is_read'] ? 'text-dark' : 'text-primary'; ?>">
                                                    <?php echo htmlspecialchars(truncateText($email['subject'], 50)); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($email['account_display_name'] ?: $email['account_email']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($email['date_received'], 'M d, Y H:i'); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/email/read.php?id=<?php echo $email['id']; ?>" class="btn btn-primary" title="Read">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="/email/compose.php?reply=<?php echo $email['id']; ?>" class="btn btn-success" title="Reply">
                                                        <i class="bi bi-reply"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($emailData['pages'] > 1): ?>
                            <nav class="p-3">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&account_id=<?php echo $filters['account_id']; ?>&search=<?php echo urlencode($filters['search']); ?>&is_read=<?php echo $filters['is_read'] ?? ''; ?>">Previous</a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $emailData['pages']; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&account_id=<?php echo $filters['account_id']; ?>&search=<?php echo urlencode($filters['search']); ?>&is_read=<?php echo $filters['is_read'] ?? ''; ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $emailData['pages']): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&account_id=<?php echo $filters['account_id']; ?>&search=<?php echo urlencode($filters['search']); ?>&is_read=<?php echo $filters['is_read'] ?? ''; ?>">Next</a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <script>
    // Select all checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.email-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
    </script>
</body>
</html>
