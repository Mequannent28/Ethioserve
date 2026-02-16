<?php
require_once 'includes/config.php';
require_once 'includes/SimpleSMTP.php';
require_once 'includes/email_service.php';

echo "<h1>Email Configuration Test</h1>";
echo "<pre>";
echo "<strong>SMTP Host:</strong> " . SMTP_HOST . "\n";
echo "<strong>SMTP Port:</strong> " . SMTP_PORT . "\n";
echo "<strong>SMTP User:</strong> " . SMTP_USER . "\n";
echo "<strong>SMTP Pass:</strong> " . (strlen(SMTP_PASS) > 0 ? "******** (Set)" : "Not Set") . "\n";
echo "</pre>";

if (SMTP_USER === 'your-email@gmail.com') {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "<h3>⚠️ Configuration Required</h3>";
    echo "<p>You have not configured your email settings yet.</p>";
    echo "<p>Please edit <strong>includes/config.php</strong> and set your Gmail address and App Password.</p>";
    echo "</div>";
} else {
    echo "<h3>Attempting to send test email...</h3>";

    $to = SMTP_USER; // Send to self
    $subject = "Test Email from EthioServe";
    $body = "<h1>It Works!</h1><p>Your email configuration is correct.</p>";

    $result = sendEmailWithAttachment($to, "Test User", $subject, $body, null, null);

    if ($result['success']) {
        echo "<div style='color: green; padding: 10px; border: 1px solid green;'>";
        echo "<h3>✅ " . $result['message'] . "</h3>";
        echo "<p>Check your inbox at " . $to . "</p>";
        echo "</div>";
    } else {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
        echo "<h3>❌ Failed to send</h3>";
        echo "<p>" . $result['message'] . "</p>";
        echo "</div>";
    }
}
?>