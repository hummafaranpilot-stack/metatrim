<?php
/**
 * Simple Native SMTP Mailer (No Third-Party Libraries)
 * Sends emails directly via SMTP socket connection
 */

class SMTPMailer {
    private $host;
    private $port;
    private $username;
    private $password;
    private $socket;
    private $debug = false;
    private $lastError = '';

    public function __construct($host, $port, $username, $password) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    public function setDebug($debug) {
        $this->debug = $debug;
    }

    public function getLastError() {
        return $this->lastError;
    }

    private $debugLog = [];

    public function getDebugLog() {
        return $this->debugLog;
    }

    private function log($message) {
        $this->debugLog[] = $message;
        if ($this->debug) {
            echo "<pre>[SMTP] " . htmlspecialchars($message) . "</pre>\n";
            flush();
        }
        error_log("[SMTP] " . $message);
    }

    private function connect() {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $protocol = ($this->port == 465) ? 'ssl://' : 'tcp://';
        $this->socket = @stream_socket_client(
            $protocol . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->socket) {
            $this->lastError = "Connection failed: $errstr ($errno)";
            $this->log($this->lastError);
            return false;
        }

        $response = $this->getResponse();
        $this->log("Connected: $response");
        return strpos($response, '220') === 0;
    }

    private function sendCommand($command, $expectedCode = null) {
        $this->log("Sending: $command");
        fwrite($this->socket, $command . "\r\n");
        $response = $this->getResponse();
        $this->log("Response: $response");

        if ($expectedCode && strpos($response, (string)$expectedCode) !== 0) {
            $this->lastError = "Unexpected response: $response";
            return false;
        }
        return $response;
    }

    private function getResponse() {
        $response = '';
        stream_set_timeout($this->socket, 10);
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return trim($response);
    }

    public function send($from, $fromName, $to, $subject, $htmlBody) {
        $this->lastError = '';

        // Connect
        if (!$this->connect()) {
            return false;
        }

        // EHLO
        $hostname = gethostname() ?: 'localhost';
        if (!$this->sendCommand("EHLO $hostname", 250)) {
            $this->close();
            return false;
        }

        // STARTTLS for port 587
        if ($this->port == 587) {
            if ($this->sendCommand("STARTTLS", 220)) {
                stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->sendCommand("EHLO $hostname", 250);
            }
        }

        // AUTH LOGIN
        if (!$this->sendCommand("AUTH LOGIN", 334)) {
            $this->close();
            return false;
        }

        // Username
        if (!$this->sendCommand(base64_encode($this->username), 334)) {
            $this->close();
            return false;
        }

        // Password
        if (!$this->sendCommand(base64_encode($this->password), 235)) {
            $this->close();
            return false;
        }

        // MAIL FROM
        if (!$this->sendCommand("MAIL FROM:<$from>", 250)) {
            $this->close();
            return false;
        }

        // RCPT TO
        if (!$this->sendCommand("RCPT TO:<$to>", 250)) {
            $this->close();
            return false;
        }

        // DATA
        if (!$this->sendCommand("DATA", 354)) {
            $this->close();
            return false;
        }

        // Build email headers and body
        $boundary = md5(time());
        $headers = [
            "Date: " . date('r'),
            "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>",
            "To: <$to>",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
            "X-Priority: 1",
            "X-MSMail-Priority: High",
            "Importance: High"
        ];

        $email = implode("\r\n", $headers) . "\r\n\r\n";
        $email .= chunk_split(base64_encode($htmlBody));
        $email .= "\r\n.";

        // Send email content
        fwrite($this->socket, $email . "\r\n");
        $response = $this->getResponse();
        $this->log("Email sent response: $response");

        if (strpos($response, '250') !== 0) {
            $this->lastError = "Failed to send: $response";
            $this->close();
            return false;
        }

        // QUIT
        $this->sendCommand("QUIT");
        $this->close();

        return true;
    }

    private function close() {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
}

/**
 * Helper function to send fraud alert email via SMTP
 */
function sendSMTPEmail($to, $subject, $htmlBody) {
    // SMTP Configuration
    $smtpHost = defined('SMTP_HOST') ? SMTP_HOST : 'mail.trustednutraproduct.com';
    $smtpPort = defined('SMTP_PORT') ? SMTP_PORT : 465;
    $smtpUser = defined('SMTP_USER') ? SMTP_USER : FRAUD_ALERT_FROM;
    $smtpPass = defined('SMTP_PASS') ? SMTP_PASS : '';

    $fromEmail = defined('FRAUD_ALERT_FROM') ? FRAUD_ALERT_FROM : $smtpUser;
    $fromName = defined('FRAUD_ALERT_FROM_NAME') ? FRAUD_ALERT_FROM_NAME : 'BuyGoods Alert';

    $mailer = new SMTPMailer($smtpHost, $smtpPort, $smtpUser, $smtpPass);
    $mailer->setDebug(true); // Enable debugging

    $result = $mailer->send($fromEmail, $fromName, $to, $subject, $htmlBody);

    if (!$result) {
        $error = $mailer->getLastError();
        error_log("SMTP Error: " . $error);
        echo "<p style='color:red'><strong>SMTP Error:</strong> " . htmlspecialchars($error) . "</p>";
    }

    return $result;
}
