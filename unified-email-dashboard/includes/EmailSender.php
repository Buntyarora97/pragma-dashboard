<?php
/**
 * Unified Email Dashboard - Email Sender Class
 * 
 * Uses PHPMailer to send emails via SMTP
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $account;
    private $mailer;
    
    /**
     * Constructor
     */
    public function __construct($account) {
        $this->account = $account;
        $this->mailer = new PHPMailer(true);
        $this->setupSMTP();
    }
    
    /**
     * Setup SMTP configuration
     */
    private function setupSMTP() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->account['smtp_host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->account['email'];
            $this->mailer->Password = $this->account['password'];
            
            // Encryption
            if ($this->account['encryption'] == 'ssl') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($this->account['encryption'] == 'tls') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $this->mailer->Port = $this->account['smtp_port'];
            
            // Additional settings
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->isHTML(true);
            
            // Set from address
            $fromName = !empty($this->account['display_name']) ? $this->account['display_name'] : $this->account['email'];
            $this->mailer->setFrom($this->account['email'], $fromName);
            
            return true;
        } catch (Exception $e) {
            error_log("SMTP Setup Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email
     */
    public function send($to, $subject, $body, $attachments = [], $isHtml = true) {
        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Add recipient
            $this->mailer->addAddress($to);
            
            // Set content
            $this->mailer->isHTML($isHtml);
            $this->mailer->Subject = $subject;
            
            if ($isHtml) {
                $this->mailer->Body = $body;
                $this->mailer->AltBody = strip_tags($body);
            } else {
                $this->mailer->Body = $body;
            }
            
            // Add attachments
            foreach ($attachments as $attachment) {
                if (file_exists($attachment['path'])) {
                    $this->mailer->addAttachment($attachment['path'], $attachment['name']);
                }
            }
            
            // Send email
            $this->mailer->send();
            
            // Log sent email
            $this->logSentEmail($to, $subject, $body, $attachments);
            
            return ['success' => true, 'message' => 'Email sent successfully!'];
        } catch (Exception $e) {
            error_log("Email Send Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Send reply to an email
     */
    public function sendReply($originalEmail, $replyBody, $attachments = []) {
        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Reply to original sender
            $this->mailer->addAddress($originalEmail['sender_email']);
            
            // Add reply-to header
            $this->mailer->addReplyTo($this->account['email']);
            
            // Set subject with Re: prefix
            $subject = $originalEmail['subject'];
            if (strpos($subject, 'Re:') !== 0) {
                $subject = 'Re: ' . $subject;
            }
            
            // Build reply body with original message quoted
            $fullBody = $this->buildReplyBody($replyBody, $originalEmail);
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $fullBody;
            $this->mailer->AltBody = strip_tags($fullBody);
            
            // Add attachments
            foreach ($attachments as $attachment) {
                if (file_exists($attachment['path'])) {
                    $this->mailer->addAttachment($attachment['path'], $attachment['name']);
                }
            }
            
            // Send email
            $this->mailer->send();
            
            // Log sent email
            $this->logSentEmail($originalEmail['sender_email'], $subject, $fullBody, $attachments);
            
            return ['success' => true, 'message' => 'Reply sent successfully!'];
        } catch (Exception $e) {
            error_log("Reply Send Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Build reply body with quoted original message
     */
    private function buildReplyBody($replyText, $originalEmail) {
        $date = formatDate($originalEmail['date_received']);
        $sender = $originalEmail['sender_name'] . ' <' . $originalEmail['sender_email'] . '>';
        
        $quotedOriginal = nl2br(htmlspecialchars($originalEmail['body']));
        
        $body = <<<HTML
<div style="font-family: Arial, sans-serif; line-height: 1.6;">
    <div style="margin-bottom: 20px;">
        {$replyText}
    </div>
    <div style="border-left: 2px solid #ccc; margin-left: 10px; padding-left: 10px; color: #666;">
        <p style="margin-bottom: 10px;">On {$date}, {$sender} wrote:</p>
        <div style="white-space: pre-wrap;">
            {$quotedOriginal}
        </div>
    </div>
</div>
HTML;
        
        return $body;
    }
    
    /**
     * Log sent email to database
     */
    private function logSentEmail($recipient, $subject, $body, $attachments) {
        try {
            $db = getDBConnection();
            
            $attachmentNames = array_map(function($a) {
                return $a['name'];
            }, $attachments);
            
            $stmt = $db->prepare("INSERT INTO sent_emails 
                (email_account_id, recipient, subject, body, attachments, status) 
                VALUES (?, ?, ?, ?, ?, 'sent')");
            
            $stmt->execute([
                $this->account['id'],
                $recipient,
                $subject,
                $body,
                json_encode($attachmentNames)
            ]);
        } catch (PDOException $e) {
            error_log("Error logging sent email: " . $e->getMessage());
        }
    }
    
    /**
     * Test SMTP connection
     */
    public static function testSMTP($config) {
        try {
            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = $config['smtp_host'];
            $mailer->SMTPAuth = true;
            $mailer->Username = $config['email'];
            $mailer->Password = $config['password'];
            
            if ($config['encryption'] == 'ssl') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($config['encryption'] == 'tls') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $mailer->Port = $config['smtp_port'];
            
            // Enable debug output
            $mailer->SMTPDebug = SMTP::DEBUG_OFF;
            
            // Try to connect
            if ($mailer->smtpConnect()) {
                $mailer->smtpClose();
                return ['success' => true, 'message' => 'SMTP connection successful!'];
            } else {
                return ['success' => false, 'message' => 'Could not connect to SMTP server'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>
