-- Unified Email Dashboard - Database Schema
-- Created for PHP Email Management System

-- Create database
CREATE DATABASE IF NOT EXISTS unified_email_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE unified_email_dashboard;

-- ============================================
-- TABLE: admins
-- Stores admin login credentials
-- ============================================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: email_accounts
-- Stores email account configurations
-- ============================================
CREATE TABLE IF NOT EXISTS email_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    imap_host VARCHAR(100) NOT NULL,
    imap_port INT NOT NULL DEFAULT 993,
    smtp_host VARCHAR(100) NOT NULL,
    smtp_port INT NOT NULL DEFAULT 587,
    encryption VARCHAR(10) NOT NULL DEFAULT 'ssl',
    provider VARCHAR(50) DEFAULT 'custom',
    display_name VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_sync DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: emails
-- Stores cached emails from all accounts
-- ============================================
CREATE TABLE IF NOT EXISTS emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_account_id INT NOT NULL,
    message_id VARCHAR(255) NOT NULL,
    sender_name VARCHAR(200),
    sender_email VARCHAR(200) NOT NULL,
    recipient VARCHAR(200),
    subject VARCHAR(500),
    body TEXT,
    body_html TEXT,
    date_received DATETIME NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    is_flagged TINYINT(1) DEFAULT 0,
    folder VARCHAR(50) DEFAULT 'INBOX',
    attachments_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (email_account_id) REFERENCES email_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_message (email_account_id, message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: attachments
-- Stores attachment metadata
-- ============================================
CREATE TABLE IF NOT EXISTS attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500),
    file_size INT,
    mime_type VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: settings
-- Stores application settings
-- ============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: sent_emails
-- Stores sent email logs
-- ============================================
CREATE TABLE IF NOT EXISTS sent_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_account_id INT NOT NULL,
    recipient VARCHAR(200) NOT NULL,
    subject VARCHAR(500),
    body TEXT,
    attachments TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'sent',
    error_message TEXT,
    FOREIGN KEY (email_account_id) REFERENCES email_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT ADMIN USER
-- Username: admin
-- Password: admin123 (CHANGE THIS AFTER INSTALLATION!)
-- ============================================
INSERT INTO admins (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');

-- ============================================
-- INSERT DEFAULT SETTINGS
-- ============================================
INSERT INTO settings (setting_key, setting_value) VALUES
('app_name', 'Unified Email Dashboard'),
('items_per_page', '25'),
('auto_sync_interval', '5'),
('theme', 'light');

-- ============================================
-- CREATE INDEXES FOR BETTER PERFORMANCE
-- ============================================
CREATE INDEX idx_emails_account ON emails(email_account_id);
CREATE INDEX idx_emails_date ON emails(date_received);
CREATE INDEX idx_emails_read ON emails(is_read);
CREATE INDEX idx_emails_folder ON emails(folder);
