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

$subject = 'Website contact form: ' . $name;
$message = "Name: {$name}\n";
$message .= "Phone: {$phone}\n";
$message .= "Email: {$email}\n\n";
$message .= "Message:\n{$text}\n";

$isLocalRequest = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', 'localhost:8000', '127.0.0.1', '127.0.0.1:8000'], true);
$showDebug = !empty($mailConfig['debug']) || $isLocalRequest;

try {
    $mailer = new SmtpMailer($mailConfig);
    $mailer->send(
        $mailConfig['to_email'],
        $subject,
        $message,
        [
            'from_email' => $mailConfig['from_email'],
            'from_name' => $mailConfig['from_name'],
            'reply_to' => $email,
        ]
    );
    echo 'OK';
} catch (Throwable $e) {
    http_response_code(500);

    if ($showDebug) {
        exit('SMTP error: ' . $e->getMessage());
    }

    exit('Unable to send your message. Please email us directly at ' . $mailConfig['to_email'] . '.');
}
