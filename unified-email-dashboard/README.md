# Unified Email Dashboard

A comprehensive PHP web application that allows companies to manage multiple email accounts from different providers (Gmail, Outlook, Hostinger, Yahoo, Custom IMAP) in a single, unified dashboard.

## Features

- **Admin Authentication**: Secure login system with password hashing and session management
- **Dashboard Overview**: View total accounts, unread emails, and recent activity
- **Email Account Management**: Add unlimited email accounts with automatic IMAP verification
- **Unified Inbox**: View all emails from all accounts in one place
- **Read & Reply**: Full email reading and reply functionality
- **Compose & Send**: Send emails using PHPMailer with SMTP
- **Email Sync**: Fetch emails via IMAP using Webklex PHP IMAP library
- **Search & Filter**: Search emails and filter by account or status
- **Bulk Actions**: Mark as read/unread, delete multiple emails
- **Responsive Design**: Bootstrap 5 admin dashboard interface
- **Security**: CSRF protection, prepared statements, input validation

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for dependency management)
- PHP Extensions: PDO, PDO_MySQL, OpenSSL, IMAP

## Installation

### Step 1: Download and Extract

Download the application files and extract them to your web server directory (e.g., `/var/www/html/` or `public_html/`).

### Step 2: Create Database

1. Create a new MySQL database:
```sql
CREATE DATABASE unified_email_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u root -p unified_email_dashboard < database.sql
```

Or use phpMyAdmin to import the `database.sql` file.

### Step 3: Configure Database

Edit `/config/database.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'your_db_username');
define('DB_PASSWORD', 'your_db_password');
define('DB_NAME', 'unified_email_dashboard');
```

### Step 4: Install Dependencies

Navigate to the project directory and run Composer:

```bash
cd /path/to/unified-email-dashboard
composer install
```

If you don't have Composer installed, download it from https://getcomposer.org/

### Step 5: Set Permissions

Ensure the web server has proper permissions:

```bash
chmod -R 755 /path/to/unified-email-dashboard
chmod -R 777 /path/to/unified-email-dashboard/uploads  # For attachments
```

### Step 6: Access the Application

Open your browser and navigate to:

```
http://your-domain.com/
```

Or if using localhost:

```
http://localhost/unified-email-dashboard/
```

### Default Login Credentials

- **Username**: `admin`
- **Password**: `admin123`

**Important**: Change the default password immediately after first login!

## Email Provider Configuration

### Gmail

1. Enable 2-Factor Authentication on your Google account
2. Generate an App Password at: https://myaccount.google.com/apppasswords
3. Use the App Password instead of your regular password

**Settings:**
- IMAP Host: `imap.gmail.com`
- IMAP Port: `993`
- SMTP Host: `smtp.gmail.com`
- SMTP Port: `465`
- Encryption: `SSL`

### Outlook / Office 365

**Settings:**
- IMAP Host: `outlook.office365.com`
- IMAP Port: `993`
- SMTP Host: `smtp.office365.com`
- SMTP Port: `587`
- Encryption: `TLS`

### Yahoo Mail

**Settings:**
- IMAP Host: `imap.mail.yahoo.com`
- IMAP Port: `993`
- SMTP Host: `smtp.mail.yahoo.com`
- SMTP Port: `465`
- Encryption: `SSL`

### Hostinger

**Settings:**
- IMAP Host: `imap.hostinger.com`
- IMAP Port: `993`
- SMTP Host: `smtp.hostinger.com`
- SMTP Port: `465`
- Encryption: `SSL`

### Custom IMAP Server

Enter your custom IMAP and SMTP server details as provided by your email hosting provider.

## Project Structure

```
unified-email-dashboard/
├── .htaccess                 # Apache configuration
├── composer.json             # Composer dependencies
├── database.sql              # Database schema
├── index.php                 # Main entry point
├── README.md                 # This file
├── config/
│   └── database.php          # Database configuration
├── auth/
│   ├── login.php             # Login page
│   └── logout.php            # Logout handler
├── dashboard/
│   └── index.php             # Main dashboard
├── email/
│   ├── accounts.php          # Email account management
│   ├── compose.php           # Compose/send emails
│   ├── inbox.php             # Unified inbox
│   ├── read.php              # Read email page
│   └── sync.php              # Sync emails from IMAP
├── includes/
│   ├── EmailFetcher.php      # IMAP email fetching class
│   ├── EmailSender.php       # SMTP email sending class
│   └── functions.php         # Helper functions
├── assets/
│   ├── css/
│   │   └── dashboard.css     # Dashboard styles
│   └── js/
│       └── dashboard.js      # Dashboard scripts
└── uploads/
    └── attachments/          # Uploaded attachments (auto-created)
```

## Usage Guide

### Adding an Email Account

1. Login to the dashboard
2. Click "Email Accounts" in the sidebar
3. Click "Add Account" button
4. Select your email provider or choose "Custom"
5. Fill in the required details:
   - Email Address
   - Password (or App Password for Gmail)
   - Display Name (optional)
6. Click "Add Account"
7. The system will verify the IMAP connection before saving

### Syncing Emails

1. Click "Sync Emails" in the sidebar
2. Select the account to sync (or "All Accounts")
3. Choose how many emails to fetch
4. Click "Sync Now"
5. View the sync results

### Reading Emails

1. Click "Unified Inbox" in the sidebar
2. Use filters to search or filter by account
3. Click on any email to read it
4. Use the action buttons to reply or delete

### Composing Emails

1. Click "Compose Email" in the sidebar
2. Select the sender account
3. Enter recipient, subject, and message
4. Add attachments if needed
5. Click "Send Email"

### Replying to Emails

1. Open the email you want to reply to
2. Click the "Reply" button
3. The original message will be quoted
4. Type your reply and click "Send Email"

## Security Features

- **Password Hashing**: Admin passwords are hashed using bcrypt
- **CSRF Protection**: All forms include CSRF tokens
- **Session Security**: Secure session handling with regeneration
- **Input Validation**: All user inputs are sanitized and validated
- **Prepared Statements**: SQL queries use prepared statements to prevent injection
- **XSS Protection**: Output is properly escaped
- **Security Headers**: HTTP security headers are configured

## Troubleshooting

### Cannot connect to Gmail

- Make sure you're using an App Password, not your regular Gmail password
- Enable "Less secure app access" (not recommended) or use App Passwords
- Check if IMAP is enabled in Gmail settings

### IMAP connection fails

- Verify your IMAP server address and port
- Check if encryption (SSL/TLS) is correctly configured
- Ensure your hosting provider allows IMAP connections
- Check firewall settings

### Emails not sending

- Verify SMTP settings
- Check if your hosting provider allows SMTP connections
- For Gmail, use App Password and enable SMTP access

### Database connection error

- Verify database credentials in `/config/database.php`
- Ensure MySQL is running
- Check if the database exists

## Updating

To update the application:

1. Backup your database
2. Download the new version
3. Replace all files except `/config/database.php`
4. Run any database migrations if provided
5. Clear your browser cache

## Support

For support, please contact your system administrator or refer to the documentation.

## License

This project is open-source and available under the MIT License.

## Credits

- [PHPMailer](https://github.com/PHPMailer/PHPMailer) - Email sending library
- [Webklex PHP IMAP](https://github.com/Webklex/php-imap) - IMAP client library
- [Bootstrap 5](https://getbootstrap.com/) - Frontend framework
- [Bootstrap Icons](https://icons.getbootstrap.com/) - Icon library
