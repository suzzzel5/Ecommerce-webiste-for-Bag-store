<?php
// Test mail sender for Gmail SMTP
// Visit: http://localhost/projectdone/test_mail.php?to=someone@example.com

require __DIR__ . '/smtp/PHPMailerAutoload.php';
$cfg = require __DIR__ . '/components/mail_config.php';

$to = isset($_GET['to']) && filter_var($_GET['to'], FILTER_VALIDATE_EMAIL)
    ? $_GET['to']
    : ($cfg['username'] ?? '');

if (!$to) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<form method="get" style="font-family:Arial,sans-serif;max-width:460px;margin:40px auto;">
            <h2>Send Test Email</h2>
            <label>Email to send:</label><br>
            <input type="email" name="to" placeholder="recipient@example.com" style="width:100%;padding:8px;margin:8px 0;" required>
            <button type="submit" style="padding:8px 14px;background:#27ae60;color:#fff;border:none;border-radius:4px;">Send</button>
          </form>';
    exit;
}

$mail = new PHPMailer(true);
try {
    // SMTP
    $mail->isSMTP();
    $mail->Host       = $cfg['host'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth   = $cfg['auth'] ?? true;
    $mail->Username   = $cfg['username'] ?? '';
    $mail->Password   = $cfg['password'] ?? '';
    $mail->SMTPSecure = $cfg['secure'] ?? 'tls';
    $mail->Port       = (int)($cfg['port'] ?? 587);
    $mail->CharSet    = 'UTF-8';

    // Verbose debug to page
    $mail->SMTPDebug   = 2;           // show client+server messages
    $mail->Debugoutput = 'html';      // write directly to output

    // From/To
    $fromEmail = $cfg['from_email'] ?? ($cfg['username'] ?? 'no-reply@example.com');
$fromName  = $cfg['from_name']  ?? 'Nexus Bag';
    $mail->setFrom($fromEmail, $fromName);
    if (!empty($cfg['reply_to'])) {
        $mail->addReplyTo($cfg['reply_to'], $fromName);
    }
    $mail->addAddress($to);

    // Content
    $mail->isHTML(true);
$mail->Subject = 'Test Email | Nexus Bag';
    $mail->Body    = '<p>This is a <strong>test email</strong> sent via Gmail SMTP.</p><p>If you can read this, SMTP works.</p>';
    $mail->AltBody = 'This is a test email sent via Gmail SMTP. If you can read this, SMTP works.';

    header('Content-Type: text/html; charset=UTF-8');
    echo '<pre style="white-space:pre-wrap;font-family:Consolas,monospace;background:#f7f7f7;padding:12px;border:1px solid #eee;">Sending to: ' . htmlspecialchars($to) . "\nFrom: " . htmlspecialchars($fromEmail) . "\nHost: " . htmlspecialchars($mail->Host) . ":" . (int)$mail->Port . " (" . htmlspecialchars($mail->SMTPSecure) . ")\n\nDebug:\n</pre>";

    $ok = $mail->send();

    echo '<div style="margin-top:10px;padding:10px;border-radius:4px;'.($ok?'background:#e6ffed;color:#046b1a;border:1px solid #b7f5c4;':'background:#ffecec;color:#7a1111;border:1px solid #f5b7b7;').'">'
       . ($ok ? 'Success: message sent.' : ('Failed: ' . htmlspecialchars($mail->ErrorInfo)))
       . '</div>';
} catch (Exception $e) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<div style="margin:20px auto;max-width:760px;padding:10px;border:1px solid #f5b7b7;background:#ffecec;color:#7a1111;border-radius:4px;">'
       . 'Exception: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
