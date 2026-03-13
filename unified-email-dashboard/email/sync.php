<?php
/**
 * Unified Email Dashboard - Sync Emails
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/EmailFetcher.php';
requireLogin();

$csrfToken = generateCSRFToken();
$results = [];

// Get all active accounts
$accounts = getEmailAccounts(true);

// Handle sync request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $results[] = ['error' => 'Invalid request. Please try again.'];
    } else {
        $accountId = intval($_POST['account_id'] ?? 0);
        $limit = intval($_POST['limit'] ?? 50);
        
        if ($accountId === 0) {
            // Sync all accounts
            foreach ($accounts as $account) {
                $fetcher = new EmailFetcher($account);
                $syncResult = $fetcher->syncEmails($limit);
                
                $results[] = [
                    'account' => $account['display_name'] ?: $account['email'],
                    'result' => $syncResult
                ];
                
                $fetcher->disconnect();
            }
        } else {
            // Sync specific account
            $account = getEmailAccountById($accountId);
            
            if ($account) {
                $fetcher = new EmailFetcher($account);
                $syncResult = $fetcher->syncEmails($limit);
                
                $results[] = [
                    'account' => $account['display_name'] ?: $account['email'],
                    'result' => $syncResult
                ];
                
                $fetcher->disconnect();
            } else {
                $results[] = ['error' => 'Account not found.'];
            }
        }
    }
}

$flashMessage = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Emails - Unified Email Dashboard</title>
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
                <li>
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
                <li class="active">
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
                    
                    <span class="navbar-brand ms-3">Sync Emails</span>
                    
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
                
                <!-- Sync Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Sync Emails from IMAP</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($accounts)): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            No email accounts configured. <a href="/email/accounts.php?action=add">Add an account</a> first.
                        </div>
                        <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="account_id" class="form-label">Email Account</label>
                                        <select class="form-select" id="account_id" name="account_id">
                                            <option value="0">All Accounts</option>
                                            <?php foreach ($accounts as $account): ?>
                                            <option value="<?php echo $account['id']; ?>">
                                                <?php echo htmlspecialchars($account['display_name'] ?: $account['email']); ?>
                                                <?php if ($account['last_sync']): ?>
                                                (Last sync: <?php echo formatDate($account['last_sync'], 'M d, H:i'); ?>)
                                                <?php else: ?>
                                                (Never synced)
                                                <?php endif; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="limit" class="form-label">Emails to Fetch</label>
                                        <select class="form-select" id="limit" name="limit">
                                            <option value="25">25 emails</option>
                                            <option value="50" selected>50 emails</option>
                                            <option value="100">100 emails</option>
                                            <option value="200">200 emails</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-arrow-repeat me-2"></i>Sync Now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Sync Results -->
                <?php if (!empty($results)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Sync Results</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($results as $result): ?>
                            <?php if (isset($result['error'])): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-x-circle-fill me-2"></i>
                                <?php echo htmlspecialchars($result['error']); ?>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-<?php echo $result['result']['success'] ? 'success' : 'danger'; ?>">
                                <strong><?php echo htmlspecialchars($result['account']); ?>:</strong>
                                <?php if ($result['result']['success']): ?>
                                <i class="bi bi-check-circle-fill me-2"></i>
                                Sync completed. <?php echo $result['result']['saved']; ?> new email(s) fetched.
                                <?php else: ?>
                                <i class="bi bi-x-circle-fill me-2"></i>
                                Sync failed: <?php echo htmlspecialchars($result['result']['error']); ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <div class="mt-3">
                            <a href="/email/inbox.php" class="btn btn-primary">
                                <i class="bi bi-inbox me-2"></i>View Inbox
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Account Status -->
                <?php if (!empty($accounts)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Account Sync Status</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Account</th>
                                        <th>Email</th>
                                        <th>Last Sync</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($accounts as $account): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($account['display_name'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($account['email']); ?></td>
                                        <td>
                                            <?php if ($account['last_sync']): ?>
                                            <?php echo formatDate($account['last_sync']); ?>
                                            <?php else: ?>
                                            <span class="text-muted">Never</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($account['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>
