<?php
/**
 * Unified Email Dashboard - Read Email
 */

require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$emailId = intval($_GET['id'] ?? 0);

if (!$emailId) {
    setFlashMessage('error', 'Invalid email ID.');
    header('Location: /email/inbox.php');
    exit();
}

// Get email details
$email = getEmailById($emailId);

if (!$email) {
    setFlashMessage('error', 'Email not found.');
    header('Location: /email/inbox.php');
    exit();
}

// Mark as read
if (!$email['is_read']) {
    markEmailAsRead($emailId);
    $email['is_read'] = 1;
}

// Get attachments
$attachments = getEmailAttachments($emailId);

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_email'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verifyCSRFToken($csrfToken)) {
        if (deleteEmail($emailId)) {
            setFlashMessage('success', 'Email deleted successfully.');
            header('Location: /email/inbox.php');
            exit();
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($email['subject']); ?> - Unified Email Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
    <style>
    .email-body {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        min-height: 300px;
    }
    .email-body iframe {
        width: 100%;
        min-height: 400px;
        border: none;
        background: white;
    }
    .email-header {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    </style>
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
                    
                    <span class="navbar-brand ms-3">Read Email</span>
                    
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
                <!-- Actions Bar -->
                <div class="mb-4">
                    <a href="/email/inbox.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Inbox
                    </a>
                    <a href="/email/compose.php?reply=<?php echo $email['id']; ?>" class="btn btn-success">
                        <i class="bi bi-reply me-2"></i>Reply
                    </a>
                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this email?');">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <button type="submit" name="delete_email" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>Delete
                        </button>
                    </form>
                </div>
                
                <!-- Email Header -->
                <div class="email-header">
                    <h4 class="mb-3"><?php echo htmlspecialchars($email['subject']); ?></h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <td style="width: 100px;"><strong>From:</strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($email['sender_name']); ?>
                                        <span class="text-muted">&lt;<?php echo htmlspecialchars($email['sender_email']); ?>&gt;</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>To:</strong></td>
                                    <td><?php echo htmlspecialchars($email['recipient']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Date:</strong></td>
                                    <td><?php echo formatDate($email['date_received'], 'F d, Y H:i:s'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Account:</strong></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($email['account_display_name'] ?: $email['account_email']); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <?php if (!$email['is_read']): ?>
                            <span class="badge bg-primary">Unread</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Read</span>
                            <?php endif; ?>
                            <?php if ($email['attachments_count'] > 0): ?>
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-paperclip"></i> <?php echo $email['attachments_count']; ?> attachment(s)
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Attachments -->
                <?php if (!empty($attachments)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-paperclip me-2"></i>Attachments</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($attachments as $attachment): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <i class="bi bi-file-earmark display-4"></i>
                                        <p class="mb-1 text-truncate" title="<?php echo htmlspecialchars($attachment['filename']); ?>">
                                            <?php echo htmlspecialchars($attachment['filename']); ?>
                                        </p>
                                        <small class="text-muted"><?php echo formatFileSize($attachment['file_size']); ?></small>
                                        <?php if ($attachment['file_path']): ?>
                                        <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" class="btn btn-sm btn-primary w-100 mt-2" download>
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Email Body -->
                <div class="email-body">
                    <?php if (!empty($email['body_html'])): ?>
                    <iframe srcdoc="<?php echo htmlspecialchars($email['body_html']); ?>"></iframe>
                    <?php else: ?>
                    <div style="white-space: pre-wrap; font-family: inherit;">
                        <?php echo nl2br(htmlspecialchars($email['body'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>
