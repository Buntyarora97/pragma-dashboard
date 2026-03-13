<?php
/**
 * Unified Email Dashboard - Email Fetcher Class
 * 
 * Uses Webklex PHP IMAP library to fetch emails from IMAP servers
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/functions.php';

use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;

class EmailFetcher {
    private $client;
    private $account;
    private $connected = false;
    
    /**
     * Constructor
     */
    public function __construct($account) {
        $this->account = $account;
    }
    
    /**
     * Connect to IMAP server
     */
    public function connect() {
        try {
            $config = [
                'host' => $this->account['imap_host'],
                'port' => $this->account['imap_port'],
                'encryption' => $this->account['encryption'],
                'validate_cert' => true,
                'username' => $this->account['email'],
                'password' => $this->account['password'],
                'protocol' => 'imap'
            ];
            
            $clientManager = new ClientManager($config);
            $this->client = $clientManager->make($config);
            $this->client->connect();
            $this->connected = true;
            
            return true;
        } catch (Exception $e) {
            error_log("IMAP Connection Error: " . $e->getMessage());
            $this->connected = false;
            return false;
        }
    }
    
    /**
     * Test connection without saving
     */
    public static function testConnection($config) {
        try {
            $clientConfig = [
                'host' => $config['imap_host'],
                'port' => $config['imap_port'],
                'encryption' => $config['encryption'],
                'validate_cert' => true,
                'username' => $config['email'],
                'password' => $config['password'],
                'protocol' => 'imap'
            ];
            
            $clientManager = new ClientManager($clientConfig);
            $client = $clientManager->make($clientConfig);
            $client->connect();
            $client->disconnect();
            
            return ['success' => true, 'message' => 'Connection successful!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Fetch emails from INBOX
     */
    public function fetchEmails($limit = 50) {
        if (!$this->connected) {
            if (!$this->connect()) {
                return [];
            }
        }
        
        try {
            $folder = $this->client->getFolder('INBOX');
            if (!$folder) {
                error_log("Could not access INBOX folder");
                return [];
            }
            
            $messages = $folder->messages()->all()->limit($limit)->get();
            $emails = [];
            
            foreach ($messages as $message) {
                $emails[] = $this->parseMessage($message);
            }
            
            return $emails;
        } catch (Exception $e) {
            error_log("Error fetching emails: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Parse IMAP message to array
     */
    private function parseMessage($message) {
        try {
            $sender = $message->getFrom()[0] ?? null;
            $senderName = $sender ? $sender->personal : 'Unknown';
            $senderEmail = $sender ? $sender->mail : 'unknown@example.com';
            
            // Get body
            $body = $message->getTextBody();
            $bodyHtml = $message->getHTMLBody();
            
            // If no text body but has HTML, strip tags
            if (empty($body) && !empty($bodyHtml)) {
                $body = strip_tags($bodyHtml);
            }
            
            // Get attachments count
            $attachments = $message->getAttachments();
            $attachmentsCount = count($attachments);
            
            return [
                'message_id' => $message->getMessageId()[0] ?? uniqid(),
                'sender_name' => $senderName,
                'sender_email' => $senderEmail,
                'recipient' => $this->account['email'],
                'subject' => $message->getSubject()[0] ?? 'No Subject',
                'body' => $body,
                'body_html' => $bodyHtml,
                'date_received' => $message->getDate()[0] ? $message->getDate()[0]->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
                'is_read' => !$message->getFlags()['unseen'],
                'attachments_count' => $attachmentsCount,
                'folder' => 'INBOX'
            ];
        } catch (Exception $e) {
            error_log("Error parsing message: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Save emails to database
     */
    public function syncEmails($limit = 50) {
        $emails = $this->fetchEmails($limit);
        $saved = 0;
        
        try {
            $db = getDBConnection();
            
            foreach ($emails as $email) {
                if (!$email) continue;
                
                // Check if email already exists
                $stmt = $db->prepare("SELECT id FROM emails WHERE email_account_id = ? AND message_id = ?");
                $stmt->execute([$this->account['id'], $email['message_id']]);
                
                if ($stmt->rowCount() == 0) {
                    // Insert new email
                    $stmt = $db->prepare("INSERT INTO emails 
                        (email_account_id, message_id, sender_name, sender_email, recipient, subject, body, body_html, date_received, is_read, attachments_count, folder) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->execute([
                        $this->account['id'],
                        $email['message_id'],
                        $email['sender_name'],
                        $email['sender_email'],
                        $email['recipient'],
                        $email['subject'],
                        $email['body'],
                        $email['body_html'],
                        $email['date_received'],
                        $email['is_read'] ? 1 : 0,
                        $email['attachments_count'],
                        $email['folder']
                    ]);
                    
                    $saved++;
                }
            }
            
            // Update last sync time
            $stmt = $db->prepare("UPDATE email_accounts SET last_sync = NOW() WHERE id = ?");
            $stmt->execute([$this->account['id']]);
            
            return ['success' => true, 'saved' => $saved];
        } catch (PDOException $e) {
            error_log("Error saving emails: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Disconnect from server
     */
    public function disconnect() {
        if ($this->connected && $this->client) {
            $this->client->disconnect();
            $this->connected = false;
        }
    }
    
    /**
     * Destructor
     */
    public function __destruct() {
        $this->disconnect();
    }
}
?>
