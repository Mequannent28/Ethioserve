<?php
/**
 * Simple SMTP Mailer Class
 * Use this to send emails via SMTP server (like Gmail) without PHPMailer or mail()
 */

class SimpleSMTP
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $debug = false;
    private $conn;

    public function __construct($host, $port, $user, $pass)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function send($to, $subject, $body, $fromName, $fromEmail, $attachments = [])
    {
        try {
            $protocol = ($this->port == 465) ? "ssl://" : "tcp://";
            if ($this->debug)
                error_log("Connecting to SMTP: {$protocol}{$this->host}:{$this->port}");

            $this->conn = stream_socket_client($protocol . $this->host . ":" . $this->port, $errno, $errstr, 15);
            if (!$this->conn) {
                throw new Exception("Could not connect to SMTP host: $errstr ($errno)");
            }
            $this->read();

            $serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
            $this->cmd("EHLO " . $serverName);

            // STARTTLS for port 587
            if ($this->port == 587) {
                $this->cmd("STARTTLS");
                if (!stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception("TLS negotiation failed");
                }
                $this->cmd("EHLO " . $serverName);
            }

            $this->cmd("AUTH LOGIN");
            $this->cmd(base64_encode($this->user));
            $this->cmd(base64_encode($this->pass));

            $this->cmd("MAIL FROM: <{$fromEmail}>");
            $this->cmd("RCPT TO: <{$to}>");
            $this->cmd("DATA");

            // Build headers
            $boundary = md5(time());
            $headers = "From: {$fromName} <{$fromEmail}>\r\n";
            $headers .= "To: {$to}\r\n";
            $headers .= "Subject: {$subject}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
            $headers .= "\r\n";

            // Body
            $message = "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $body . "\r\n\r\n";

            // Attachments
            foreach ($attachments as $att) {
                if (file_exists($att['path'])) {
                    $content = chunk_split(base64_encode(file_get_contents($att['path'])));
                    $message .= "--{$boundary}\r\n";
                    $message .= "Content-Type: {$att['type']}; name=\"{$att['name']}\"\r\n";
                    $message .= "Content-Transfer-Encoding: base64\r\n";
                    $message .= "Content-Disposition: attachment; filename=\"{$att['name']}\"\r\n\r\n";
                    $message .= $content . "\r\n\r\n";
                }
            }
            $message .= "--{$boundary}--\r\n";
            $message .= ".";

            $this->cmd($message);
            $this->cmd("QUIT");

            fclose($this->conn);
            return true;
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }

    private function cmd($command)
    {
        if ($this->debug)
            error_log("SMTP > $command");
        fputs($this->conn, $command . "\r\n");
        return $this->read();
    }

    private function read()
    {
        $response = "";
        while ($str = fgets($this->conn, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ")
                break;
        }
        if ($this->debug)
            error_log("SMTP < $response");
        return $response;
    }
}
?>