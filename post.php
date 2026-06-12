<?php

require_once __DIR__ . '/lib/SmtpMailer.php';

$configPath = __DIR__ . '/config/mail.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    exit('Mail is not configured. Copy config/mail.example.php to config/mail.php and add your SMTP credentials.');
}

$mailConfig = require $configPath;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$text = trim($_POST['text'] ?? '');

if ($name === '' || $email === '' || $text === '') {
    http_response_code(400);
    exit('Please fill in your name, email, and message.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit('Please enter a valid email address.');
}

$subject = 'Website contact form: ' . preg_replace('/[\r\n]+/', ' ', $name);
$message = "Name: {$name}\n";
$message .= "Phone: {$phone}\n";
$message .= "Email: {$email}\n\n";
$message .= "Message:\n{$text}\n";

$isLocalRequest = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', 'localhost:8000', '127.0.0.1', '127.0.0.1:8000'], true);
$showDebug = !empty($mailConfig['debug']);

function buildSmtpAttempts(array $config): array
{
    $shared = [
        'from_email' => $config['from_email'],
        'from_name' => $config['from_name'],
        'to_email' => $config['to_email'],
        'timeout' => (int) ($config['timeout'] ?? 10),
    ];

    $withAuth = array_merge($shared, [
        'username' => $config['username'],
        'password' => $config['password'],
    ]);

    $withoutAuth = array_merge($shared, [
        'username' => '',
        'password' => '',
    ]);

    // cPanel: local relay on port 25 usually works without auth from PHP on the same server.
    // Do not use feenixmarketing.com here — that domain is behind Cloudflare and SMTP ports time out.
    $attempts = [
        array_merge($withoutAuth, ['host' => 'localhost', 'port' => 25, 'encryption' => 'none']),
        array_merge($withoutAuth, ['host' => '127.0.0.1', 'port' => 25, 'encryption' => 'none']),
        array_merge($withAuth, ['host' => 'localhost', 'port' => 25, 'encryption' => 'none']),
        array_merge($withAuth, ['host' => 'localhost', 'port' => 587, 'encryption' => 'tls']),
        array_merge($withAuth, ['host' => 'localhost', 'port' => 465, 'encryption' => 'ssl']),
    ];

    if (!empty($config['host']) && !in_array($config['host'], ['localhost', '127.0.0.1', 'feenixmarketing.com'], true)) {
        $attempts[] = array_merge($withAuth, [
            'host' => $config['host'],
            'port' => (int) $config['port'],
            'encryption' => $config['encryption'] ?? 'none',
        ]);
    }

    return $attempts;
}

function sendViaPhpMail(array $config, string $to, string $subject, string $body, string $replyTo): bool
{
    $fromEmail = $config['from_email'];
    $from = $config['from_name'] !== ''
        ? $config['from_name'] . ' <' . $fromEmail . '>'
        : $fromEmail;

    $headers = [
        'From: ' . $from,
        'Reply-To: ' . $replyTo,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ];

    @ini_set('sendmail_from', $fromEmail);

    return @mail(
        $to,
        $subject,
        $body,
        implode("\r\n", $headers),
        '-f' . $fromEmail
    );
}

$mailOptions = [
    'from_email' => $mailConfig['from_email'],
    'from_name' => $mailConfig['from_name'],
    'reply_to' => $email,
];

$errors = [];

if (sendViaPhpMail($mailConfig, $mailConfig['to_email'], $subject, $message, $email)) {
    echo 'OK';
    exit;
}

$errors[] = 'php mail() - sending failed';

if ($isLocalRequest && !$showDebug) {
    http_response_code(500);
    exit('Email cannot be sent from local development. Please test on the live website.');
}

foreach (buildSmtpAttempts($mailConfig) as $attempt) {
    try {
        $mailer = new SmtpMailer($attempt);
        $mailer->send($mailConfig['to_email'], $subject, $message, $mailOptions);
        echo 'OK';
        exit;
    } catch (Throwable $e) {
        $auth = ($attempt['username'] ?? '') !== '' ? 'auth' : 'no-auth';
        $label = $attempt['host'] . ':' . $attempt['port'] . '/' . ($attempt['encryption'] ?? 'none') . '/' . $auth;
        $errors[] = $label . ' - ' . $e->getMessage();
    }
}

http_response_code(500);

if ($showDebug) {
    exit('Mail error: ' . implode(' | ', $errors));
}

exit('Unable to send your message. Please email us directly at ' . $mailConfig['to_email'] . '.');
