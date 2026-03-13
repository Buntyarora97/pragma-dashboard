<?php
/**
 * Unified Email Dashboard - Admin Login
 */

require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /dashboard/');
    exit();
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            try {
                $db = getDBConnection();
                $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($password, $admin['password'])) {
                    // Login successful
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    
                    // Update last login
                    $stmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$admin['id']]);
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    // Redirect to dashboard
                    header('Location: /dashboard/');
                    exit();
                } else {
                    $error = 'Invalid username or password.';
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
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
    <title>Login - Unified Email Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo i {
            font-size: 60px;
            color: #667eea;
        }
        .login-logo h2 {
            margin-top: 15px;
            color: #333;
            font-weight: 600;
        }
        .form-floating {
            margin-bottom: 15px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 500;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #5a6fd6 0%, #6a4190 100%);
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="bi bi-envelope-fill"></i>
            <h2>Unified Email Dashboard</h2>
            <p class="text-muted">Admin Login</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required autofocus>
                <label for="username"><i class="bi bi-person me-2"></i>Username</label>
            </div>
            
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-login w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </button>
        </form>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                Default: admin / admin123
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
