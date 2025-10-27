<?php
/* * *************************************************************************
 *  Copyright (C) 2010  William Bellavance
 *                      Flintman Computers
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * ************************************************************************* */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**$mail = new PHPMailer(true);
 * PHPMailer-based Mail Class for FlintmanCMS
 * Provides easy-to-use email functionality with full SMTP support
 */
class Mail {
    private $phpmailer;
    private $smtpServer;
    private $port;
    private $username;
    private $password;
    private $fromEmail;
    private $fromName;
    private $encryption;
    private $debug;
    private $Error = '';

    /**
     * Constructor - Initialize the Mail class
     * 
     * @param string $server SMTP server hostname
     * @param string $username SMTP username
     * @param string $password SMTP password
     * @param string $fromEmail From email address
     * @param int $port SMTP port (default: 25)
     * @param string $encryption Encryption type: 'none', 'ssl', 'tls' (default: 'none')
     * @param string $fromName From name (default: same as email)
     * @param bool $debug Enable debug output (default: false)
     */
    public function __construct($server, $username, $password, $fromEmail, $port = 465, $encryption = 'tls', $fromName = '', $debug = false) {
        $this->smtpServer = $server;
        $this->username = $username;
        $this->password = $password;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName ?: $fromEmail;
        $this->port = (int)$port;
        $this->encryption = strtolower($encryption);
        $this->debug = $debug;
        
        // Create new PHPMailer instance
        $this->phpmailer = new PHPMailer(true);
        $this->configureSMTP();
    }

    /**
     * Configure SMTP settings
     */
    private function configureSMTP() {
        try {
            // Server settings
            $this->phpmailer->isSMTP();
            $this->phpmailer->Host = $this->smtpServer;
            $this->phpmailer->SMTPAuth = true;
            $this->phpmailer->Username = $this->username;
            $this->phpmailer->Password = $this->password;
            $this->phpmailer->Port = $this->port;
            $this->phpmailer->CharSet = 'UTF-8';
            
            // Debug settings
            if ($this->debug) {
                $this->phpmailer->SMTPDebug = SMTP::DEBUG_SERVER;
            } else {
                $this->phpmailer->SMTPDebug = 0;
            }
            
            // Set encryption
            switch ($this->encryption) {
                case 'ssl':
                    $this->phpmailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    break;
                case 'tls':
                case 'starttls':
                    $this->phpmailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    break;
                default:
                    $this->phpmailer->SMTPSecure = false;
                    $this->phpmailer->SMTPAutoTLS = false;
                    break;
            }
            
            // Set default from address
            $this->phpmailer->setFrom($this->fromEmail, $this->fromName);
            
        } catch (Exception $e) {
            $this->Error = "SMTP Configuration Error: " . $e->getMessage();
        }
    }

    /**
     * Send an email
     * 
     * @param string|array $to Recipient email address(es)
     * @param string $subject Email subject
     * @param string $message Email body (HTML or plain text)
     * @param bool $isHTML Whether the message is HTML (default: true)
     * @param string|array $cc CC email address(es) (optional)
     * @param string|array $bcc BCC email address(es) (optional)
     * @param string $replyTo Reply-to email address (optional)
     * @param array $attachments Array of file paths to attach (optional)
     * @return bool True on success, false on failure
     */
    public function sendEmail($to, $subject, $message, $isHTML = true, $cc = null, $bcc = null, $replyTo = null, $attachments = []) {
        try {
            // Clear previous recipients and attachments
            $this->phpmailer->clearAddresses();
            $this->phpmailer->clearCCs();
            $this->phpmailer->clearBCCs();
            $this->phpmailer->clearAttachments();
            $this->phpmailer->clearReplyTos();
            
            // Add recipients
            if (is_array($to)) {
                foreach ($to as $email => $name) {
                    if (is_numeric($email)) {
                        $this->phpmailer->addAddress($name);
                    } else {
                        $this->phpmailer->addAddress($email, $name);
                    }
                }
            } else {
                $this->phpmailer->addAddress($to);
            }
            
            // Add CC recipients
            if ($cc) {
                if (is_array($cc)) {
                    foreach ($cc as $email => $name) {
                        if (is_numeric($email)) {
                            $this->phpmailer->addCC($name);
                        } else {
                            $this->phpmailer->addCC($email, $name);
                        }
                    }
                } else {
                    $this->phpmailer->addCC($cc);
                }
            }
            
            // Add BCC recipients
            if ($bcc) {
                if (is_array($bcc)) {
                    foreach ($bcc as $email => $name) {
                        if (is_numeric($email)) {
                            $this->phpmailer->addBCC($name);
                        } else {
                            $this->phpmailer->addBCC($email, $name);
                        }
                    }
                } else {
                    $this->phpmailer->addBCC($bcc);
                }
            }
            
            // Set reply-to
            if ($replyTo) {
                $this->phpmailer->addReplyTo($replyTo);
            }
            
            // Add attachments
            if (!empty($attachments)) {
                foreach ($attachments as $file) {
                    if (is_array($file)) {
                        // Array format: ['path' => '/path/to/file', 'name' => 'filename.ext']
                        $this->phpmailer->addAttachment($file['path'], $file['name'] ?? '');
                    } else {
                        // Simple string path
                        $this->phpmailer->addAttachment($file);
                    }
                }
            }
            
            // Content
            $this->phpmailer->isHTML($isHTML);
            $this->phpmailer->Subject = $subject;
            $this->phpmailer->Body = $message;
            
            // If HTML, create plain text version
            if ($isHTML) {
                $this->phpmailer->AltBody = strip_tags($message);
            }
            
            $this->phpmailer->send();
            return true;
            
        } catch (Exception $e) {
            $this->Error = "Message could not be sent. Mailer Error: {$this->phpmailer->ErrorInfo}";
            return false;
        }
    }

    /**
     * Simple email sending method (backward compatibility)
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $message Email body
     * @return bool True on success, false on failure
     */
    public function mailit($to, $subject, $message) {
        return $this->sendEmail($to, $subject, $message, true);
    }

    /**
     * Test SMTP connection
     * 
     * @return array Array with 'success' (bool) and 'message' (string)
     */
    public function testConnection() {
        try {
            $this->phpmailer->SMTPDebug = 0; // Disable debug for testing
            $result = $this->phpmailer->smtpConnect();
            if ($result) {
                $this->phpmailer->smtpClose();
                return ['success' => true, 'message' => 'SMTP connection successful'];
            } else {
                return ['success' => false, 'message' => 'SMTP connection failed'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Connection error: ' . $e->getMessage()];
        }
    }

    /**
     * Get the last error message
     * 
     * @return string Error message
     */
    public function printError() {
        return $this->Error;
    }

    /**
     * Set debug mode
     * 
     * @param bool $debug Enable/disable debug mode
     */
    public function setDebug($debug = true) {
        $this->debug = $debug;
        $this->phpmailer->SMTPDebug = $debug ? SMTP::DEBUG_SERVER : 0;
    }

    /**
     * Set character encoding
     * 
     * @param string $charset Character encoding (default: UTF-8)
     */
    public function setCharset($charset = 'UTF-8') {
        $this->phpmailer->CharSet = $charset;
    }

    /**
     * Override the default from address for this instance
     * 
     * @param string $email From email address
     * @param string $name From name (optional)
     */
    public function setFrom($email, $name = '') {
        try {
            $this->phpmailer->setFrom($email, $name ?: $email);
            $this->fromEmail = $email;
            $this->fromName = $name ?: $email;
        } catch (Exception $e) {
            $this->Error = "Error setting from address: " . $e->getMessage();
        }
    }

    /**
     * Destructor - Clean up PHPMailer instance
     */
    public function __destruct() {
        if ($this->phpmailer) {
            $this->phpmailer->clearAddresses();
            $this->phpmailer->clearAttachments();
            $this->phpmailer->clearCCs();
            $this->phpmailer->clearBCCs();
            $this->phpmailer->clearReplyTos();
        }
    }
}

?>