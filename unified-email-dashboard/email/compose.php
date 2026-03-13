<?php
/**
 * Unified Email Dashboard - Compose Email
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/EmailSender.php';
requireLogin();

$error = '';
$success = '';
$csrfToken = generateCSRFToken();

// Get all active email accounts
$accounts = getEmailAccounts(true);

if (empty($accounts)) {
    setFlashMessage('error', 'Please add an email account first.');
    header('Location: /email/accounts.php?action=add');
    exit();
}

// Check if replying to an email
$replyTo = null;
if (isset($_GET['reply'])) {
    $replyTo = getEmailById(intval($_GET['reply']));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $accountId = intval($_POST['account_id'] ?? 0);
        $to = sanitize($_POST['to'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $message = $_POST['message'] ?? '';
        $isReply = isset($_POST['is_reply']) && $_POST['is_reply'] == '1';
        $replyEmailId = intval($_POST['reply_email_id'] ?? 0);
        
        // Validation
        if ($accountId <= 0) {
            $error = 'Please select a sender email account.';
        } elseif (empty($to)) {
            $error = 'Please enter recipient email address.';
        } elseif (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid recipient email address.';
        } elseif (empty($subject)) {
            $error = 'Please enter email subject.';
        } elseif (empty($message)) {
            $error = 'Please enter email message.';
        } else {
            // Get account details
            $account = getEmailAccountById($accountId);
            
            if (!$account) {
                $error = 'Selected email account not found.';
            } else {
                // Handle attachments
                $attachments = [];
                if (!empty($_FILES['attachments']['name'][0])) {
                    $uploadDir = __DIR__ . '/../uploads/attachments/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                            $filename = basename($_FILES['attachments']['name'][$key]);
                            $filepath = $uploadDir . uniqid() . '_' . $filename;
                            
                            if (move_uploaded_file($tmpName, $filepath)) {
                                $attachments[] = [
                                    'path' => $filepath,
                                    'name' => $filename
                                ];
                            }
                        }
                    }
                }
                
                // Send email
                $sender = new EmailSender($account);
                
                if ($isReply && $replyEmailId > 0) {
                    $originalEmail = getEmailById($replyEmailId);
                    if ($originalEmail) {
                        $result = $sender->sendReply($originalEmail, $message, $attachments);
                    } else {
                        $error = 'Original email not found.';
                    }
                } else {
                    $result = $sender->send($to, $subject, nl2br(htmlspecialchars($message)), $attachments);
                }
                
                if (isset($result) && $result['success']) {
                    setFlashMessage('success', $result['message']);
                    header('Location: /email/inbox.php');
                    exit();
                } else {
                    $error = $result['message'] ?? 'Failed to send email.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $replyTo ? 'Reply' : 'Compose'; ?> Email - Unified Email Dashboard</title>
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
                <li class="active">
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
                    
                    <span class="navbar-brand ms-3"><?php echo $replyTo ? 'Reply' : 'Compose'; ?> Email</span>
                    
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
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-<?php echo $replyTo ? 'reply' : 'pencil-square'; ?> me-2"></i>
                            <?php echo $replyTo ? 'Reply to Email' : 'New Email'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <?php if ($replyTo): ?>
                            <input type="hidden" name="is_reply" value="1">
                            <input type="hidden" name="reply_email_id" value="<?php echo $replyTo['id']; ?>">
                            
                            <div class="alert alert-info">
                                <strong>Replying to:</strong> <?php echo htmlspecialchars($replyTo['subject']); ?><br>
                                <strong>From:</strong> <?php echo htmlspecialchars($replyTo['sender_name'] ?: $replyTo['sender_email']); ?> &lt;<?php echo htmlspecialchars($replyTo['sender_email']); ?>&gt;
                            </div>
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="account_id" class="form-label">From (Sender Account) *</label>
                                        <select class="form-select" id="account_id" name="account_id" required>
                                            <option value="">Select Account...</option>
                                            <?php foreach ($accounts as $account): ?>
                                            <option value="<?php echo $account['id']; ?>" <?php echo ($replyTo && $replyTo['email_account_id'] == $account['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($account['display_name'] ?: $account['email']); ?> (<?php echo htmlspecialchars($account['email']); ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="to" class="form-label">To *</label>
                                        <input type="email" class="form-control" id="to" name="to" required 
                                               value="<?php echo $replyTo ? htmlspecialchars($replyTo['sender_email']) : ''; ?>"
                                               <?php echo $replyTo ? 'readonly' : ''; ?>
                                               placeholder="recipient@example.com">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject *</label>
                                <input type="text" class="form-control" id="subject" name="subject" required
                                       value="<?php echo $replyTo ? 'Re: ' . htmlspecialchars($replyTo['subject']) : ''; ?>"
                                       placeholder="Email subject">
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Message *</label>
                                <textarea class="form-control" id="message" name="message" rows="12" required placeholder="Type your message here..."><?php 
                                if ($replyTo) {
                                    echo "\n\n--- Original Message ---\n";
                                    echo "From: " . $replyTo['sender_name'] . " <" . $replyTo['sender_email'] . ">\n";
                                    echo "Date: " . formatDate($replyTo['date_received']) . "\n";
                                    echo "Subject: " . $replyTo['subject'] . "\n\n";
                                    echo strip_tags($replyTo['body']);
                                }
                                ?></textarea>
                            </div>
                            
                            <?php if (!$replyTo): ?>
                            <div class="mb-3">
                                <label for="attachments" class="form-label">Attachments</label>
                                <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                                <div class="form-text">You can select multiple files. Max file size depends on your server configuration.</div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send me-2"></i>Send Email
                                </button>
                                <a href="/email/inbox.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>
