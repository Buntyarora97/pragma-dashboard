<?php
/**
 * Unified Email Dashboard - Helper Functions
 */

require_once __DIR__ . '/../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if admin is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Require login - redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /auth/login.php');
        exit();
    }
}

/**
 * Sanitize input
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Get all email accounts
 */
function getEmailAccounts($activeOnly = true) {
    try {
        $db = getDBConnection();
        $sql = "SELECT * FROM email_accounts";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching email accounts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get single email account by ID
 */
function getEmailAccountById($id) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT * FROM email_accounts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching email account: " . $e->getMessage());
        return false;
    }
}

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    try {
        $db = getDBConnection();
        $stats = [];
        
        // Total email accounts
        $stmt = $db->query("SELECT COUNT(*) as total FROM email_accounts WHERE is_active = 1");
        $stats['total_accounts'] = $stmt->fetch()['total'];
        
        // Total unread emails
        $stmt = $db->query("SELECT COUNT(*) as total FROM emails WHERE is_read = 0");
        $stats['unread_emails'] = $stmt->fetch()['total'];
        
        // Total emails
        $stmt = $db->query("SELECT COUNT(*) as total FROM emails");
        $stats['total_emails'] = $stmt->fetch()['total'];
        
        // Recent emails (last 7 days)
        $stmt = $db->query("SELECT COUNT(*) as total FROM emails WHERE date_received >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stats['recent_emails'] = $stmt->fetch()['total'];
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error fetching dashboard stats: " . $e->getMessage());
        return [
            'total_accounts' => 0,
            'unread_emails' => 0,
            'total_emails' => 0,
            'recent_emails' => 0
        ];
    }
}

/**
 * Get emails for unified inbox
 */
function getEmails($filters = [], $page = 1, $perPage = 25) {
    try {
        $db = getDBConnection();
        $where = [];
        $params = [];
        
        if (!empty($filters['account_id'])) {
            $where[] = "e.email_account_id = ?";
            $params[] = $filters['account_id'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(e.subject LIKE ? OR e.sender_email LIKE ? OR e.sender_name LIKE ? OR e.body LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (isset($filters['is_read'])) {
            $where[] = "e.is_read = ?";
            $params[] = $filters['is_read'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM emails e " . $whereClause;
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];
        
        // Get emails
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT e.*, ea.email as account_email, ea.display_name as account_display_name 
                FROM emails e 
                LEFT JOIN email_accounts ea ON e.email_account_id = ea.id 
                " . $whereClause . " 
                ORDER BY e.date_received DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $db->prepare($sql);
        $params[] = $perPage;
        $params[] = $offset;
        $stmt->execute($params);
        $emails = $stmt->fetchAll();
        
        return [
            'emails' => $emails,
            'total' => $total,
            'pages' => ceil($total / $perPage),
            'current_page' => $page
        ];
    } catch (PDOException $e) {
        error_log("Error fetching emails: " . $e->getMessage());
        return ['emails' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
    }
}

/**
 * Get single email by ID
 */
function getEmailById($id) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT e.*, ea.email as account_email, ea.display_name as account_display_name 
                              FROM emails e 
                              LEFT JOIN email_accounts ea ON e.email_account_id = ea.id 
                              WHERE e.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching email: " . $e->getMessage());
        return false;
    }
}

/**
 * Get email attachments
 */
function getEmailAttachments($emailId) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT * FROM attachments WHERE email_id = ?");
        $stmt->execute([$emailId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching attachments: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark email as read
 */
function markEmailAsRead($id) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("UPDATE emails SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error marking email as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete email
 */
function deleteEmail($id) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("DELETE FROM emails WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error deleting email: " . $e->getMessage());
        return false;
    }
}

/**
 * Get provider presets
 */
function getProviderPresets() {
    return [
        'gmail' => [
            'name' => 'Gmail',
            'imap_host' => 'imap.gmail.com',
            'imap_port' => 993,
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 465,
            'encryption' => 'ssl'
        ],
        'outlook' => [
            'name' => 'Outlook',
            'imap_host' => 'outlook.office365.com',
            'imap_port' => 993,
            'smtp_host' => 'smtp.office365.com',
            'smtp_port' => 587,
            'encryption' => 'tls'
        ],
        'yahoo' => [
            'name' => 'Yahoo Mail',
            'imap_host' => 'imap.mail.yahoo.com',
            'imap_port' => 993,
            'smtp_host' => 'smtp.mail.yahoo.com',
            'smtp_port' => 465,
            'encryption' => 'ssl'
        ],
        'hostinger' => [
            'name' => 'Hostinger',
            'imap_host' => 'imap.hostinger.com',
            'imap_port' => 993,
            'smtp_host' => 'smtp.hostinger.com',
            'smtp_port' => 465,
            'encryption' => 'ssl'
        ],
        'custom' => [
            'name' => 'Custom IMAP',
            'imap_host' => '',
            'imap_port' => 993,
            'smtp_host' => '',
            'smtp_port' => 587,
            'encryption' => 'ssl'
        ]
    ];
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y h:i A') {
    if (empty($date)) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Truncate text
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Show flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Encrypt password for storage
 */
function encryptPassword($password) {
    $key = $_SESSION['csrf_token'] ?? 'default-key-change-this';
    return base64_encode(openssl_encrypt($password, 'AES-256-CBC', $key, 0, substr($key, 0, 16)));
}

/**
 * Decrypt password
 */
function decryptPassword($encryptedPassword) {
    $key = $_SESSION['csrf_token'] ?? 'default-key-change-this';
    return openssl_decrypt(base64_decode($encryptedPassword), 'AES-256-CBC', $key, 0, substr($key, 0, 16));
}

/**
 * Log activity
 */
function logActivity($action, $details = '') {
    error_log("[" . date('Y-m-d H:i:s') . "] Action: $action | Details: $details");
}
?>
