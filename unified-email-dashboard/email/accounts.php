<?php
/**
 * Unified Email Dashboard - Email Account Management
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/EmailFetcher.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';
$csrfToken = generateCSRFToken();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Add new account
        if (isset($_POST['add_account'])) {
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $displayName = sanitize($_POST['display_name'] ?? '');
            $provider = $_POST['provider'] ?? 'custom';
            $imapHost = sanitize($_POST['imap_host'] ?? '');
            $imapPort = intval($_POST['imap_port'] ?? 993);
            $smtpHost = sanitize($_POST['smtp_host'] ?? '');
            $smtpPort = intval($_POST['smtp_port'] ?? 587);
            $encryption = $_POST['encryption'] ?? 'ssl';
            
            // Validate required fields
            if (empty($email) || empty($password) || empty($imapHost) || empty($smtpHost)) {
                $error = 'Please fill in all required fields.';
            } else {
                // Test IMAP connection first
                $testConfig = [
                    'email' => $email,
                    'password' => $password,
                    'imap_host' => $imapHost,
                    'imap_port' => $imapPort,
                    'encryption' => $encryption
                ];
                
                $testResult = EmailFetcher::testConnection($testConfig);
                
                if (!$testResult['success']) {
                    $error = 'IMAP connection failed: ' . $testResult['message'];
                } else {
                    // Save to database
                    try {
                        $db = getDBConnection();
                        $stmt = $db->prepare("INSERT INTO email_accounts 
                            (email, password, display_name, provider, imap_host, imap_port, smtp_host, smtp_port, encryption) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        
                        $stmt->execute([
                            $email,
                            $password,
                            $displayName,
                            $provider,
                            $imapHost,
                            $imapPort,
                            $smtpHost,
                            $smtpPort,
                            $encryption
                        ]);
                        
                        setFlashMessage('success', 'Email account added successfully!');
                        header('Location: /email/accounts.php');
                        exit();
                    } catch (PDOException $e) {
                        error_log("Error saving account: " . $e->getMessage());
                        $error = 'Error saving account. Please try again.';
                    }
                }
            }
        }
        
        // Delete account
        if (isset($_POST['delete_account'])) {
            $accountId = intval($_POST['account_id'] ?? 0);
            
            if ($accountId > 0) {
                try {
                    $db = getDBConnection();
                    $stmt = $db->prepare("DELETE FROM email_accounts WHERE id = ?");
                    $stmt->execute([$accountId]);
                    
                    setFlashMessage('success', 'Email account deleted successfully!');
                    header('Location: /email/accounts.php');
                    exit();
                } catch (PDOException $e) {
                    error_log("Error deleting account: " . $e->getMessage());
                    $error = 'Error deleting account. Please try again.';
                }
            }
        }
        
        // Toggle account status
        if (isset($_POST['toggle_status'])) {
            $accountId = intval($_POST['account_id'] ?? 0);
            $newStatus = intval($_POST['new_status'] ?? 1);
            
            if ($accountId > 0) {
                try {
                    $db = getDBConnection();
                    $stmt = $db->prepare("UPDATE email_accounts SET is_active = ? WHERE id = ?");
                    $stmt->execute([$newStatus, $accountId]);
                    
                    setFlashMessage('success', 'Account status updated successfully!');
                    header('Location: /email/accounts.php');
                    exit();
                } catch (PDOException $e) {
                    error_log("Error updating account: " . $e->getMessage());
                    $error = 'Error updating account. Please try again.';
                }
            }
        }
    }
}

// Get all accounts
$accounts = getEmailAccounts(false);
$providerPresets = getProviderPresets();

// Get flash message
$flashMessage = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Accounts - Unified Email Dashboard</title>
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
                <li class="active">
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
                    
                    <span class="navbar-brand ms-3">Email Accounts</span>
                    
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
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($action === 'add'): ?>
                <!-- Add Account Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add Email Account</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="addAccountForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="add_account" value="1">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="provider" class="form-label">Email Provider</label>
                                        <select class="form-select" id="provider" name="provider" onchange="fillProviderSettings()">
                                            <?php foreach ($providerPresets as $key => $preset): ?>
                                            <option value="<?php echo $key; ?>" data-settings='<?php echo json_encode($preset); ?>'>
                                                <?php echo $preset['name']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="display_name" class="form-label">Display Name</label>
                                        <input type="text" class="form-control" id="display_name" name="display_name" placeholder="e.g., Support Team">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" required placeholder="support@example.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password *</label>
                                        <input type="password" class="form-control" id="password" name="password" required placeholder="Email password or app password">
                                        <div class="form-text">For Gmail, use App Password. <a href="https://myaccount.google.com/apppasswords" target="_blank">Get App Password</a></div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <h6 class="mb-3">IMAP Settings (Incoming)</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="imap_host" class="form-label">IMAP Host *</label>
                                        <input type="text" class="form-control" id="imap_host" name="imap_host" required placeholder="imap.example.com">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="imap_port" class="form-label">IMAP Port *</label>
                                        <input type="number" class="form-control" id="imap_port" name="imap_port" required value="993">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="encryption" class="form-label">Encryption</label>
                                        <select class="form-select" id="encryption" name="encryption">
                                            <option value="ssl">SSL</option>
                                            <option value="tls">TLS</option>
                                            <option value="none">None</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <h6 class="mb-3">SMTP Settings (Outgoing)</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_host" class="form-label">SMTP Host *</label>
                                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" required placeholder="smtp.example.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_port" class="form-label">SMTP Port *</label>
                                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" required value="587">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Add Account
                                </button>
                                <a href="/email/accounts.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- Account List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-envelope-at me-2"></i>Email Accounts</h5>
                        <a href="/email/accounts.php?action=add" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle me-2"></i>Add Account
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($accounts)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-envelope display-1"></i>
                            <h4 class="mt-3">No Email Accounts</h4>
                            <p class="mb-3">Add your first email account to get started.</p>
                            <a href="/email/accounts.php?action=add" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Add Account
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Email</th>
                                        <th>Display Name</th>
                                        <th>Provider</th>
                                        <th>IMAP Server</th>
                                        <th>Status</th>
                                        <th>Last Sync</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($accounts as $account): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($account['email']); ?></td>
                                        <td><?php echo htmlspecialchars($account['display_name'] ?: '-'); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo ucfirst($account['provider']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($account['imap_host']); ?></td>
                                        <td>
                                            <?php if ($account['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $account['last_sync'] ? formatDate($account['last_sync'], 'M d, H:i') : 'Never'; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                                    <input type="hidden" name="new_status" value="<?php echo $account['is_active'] ? 0 : 1; ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-<?php echo $account['is_active'] ? 'warning' : 'success'; ?>" title="<?php echo $account['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="bi bi-<?php echo $account['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this account?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                                    <button type="submit" name="delete_account" class="btn btn-danger" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Provider Configuration Reference -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Provider Configuration Reference</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Gmail</h6>
                                <ul class="list-unstyled text-muted small">
                                    <li><strong>IMAP:</strong> imap.gmail.com:993 (SSL)</li>
                                    <li><strong>SMTP:</strong> smtp.gmail.com:465 (SSL)</li>
                                    <li><strong>Note:</strong> Use <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Outlook / Office 365</h6>
                                <ul class="list-unstyled text-muted small">
                                    <li><strong>IMAP:</strong> outlook.office365.com:993 (SSL)</li>
                                    <li><strong>SMTP:</strong> smtp.office365.com:587 (TLS)</li>
                                </ul>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6>Yahoo Mail</h6>
                                <ul class="list-unstyled text-muted small">
                                    <li><strong>IMAP:</strong> imap.mail.yahoo.com:993 (SSL)</li>
                                    <li><strong>SMTP:</strong> smtp.mail.yahoo.com:465 (SSL)</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Hostinger</h6>
                                <ul class="list-unstyled text-muted small">
                                    <li><strong>IMAP:</strong> imap.hostinger.com:993 (SSL)</li>
                                    <li><strong>SMTP:</strong> smtp.hostinger.com:465 (SSL)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/dashboard.js"></script>
    <script>
    function fillProviderSettings() {
        const provider = document.getElementById('provider');
        const selected = provider.options[provider.selectedIndex];
        const settings = JSON.parse(selected.getAttribute('data-settings'));
        
        if (settings.imap_host) {
            document.getElementById('imap_host').value = settings.imap_host;
            document.getElementById('imap_port').value = settings.imap_port;
            document.getElementById('smtp_host').value = settings.smtp_host;
            document.getElementById('smtp_port').value = settings.smtp_port;
            document.getElementById('encryption').value = settings.encryption;
        }
    }
    
    // Fill on page load
    document.addEventListener('DOMContentLoaded', fillProviderSettings);
    </script>
</body>
</html>
