<?php

class SmtpMailer
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private int $timeout;

    public function __construct(array $config)
    {
        $this->host = $config['host'];
        $this->port = (int) $config['port'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->encryption = $config['encryption'] ?? 'none';
        $this->timeout = (int) ($config['timeout'] ?? 10);
    }

    public function send(string $to, string $subject, string $body, array $options = []): void
    {
        $fromEmail = $options['from_email'] ?? $this->username;
        $fromName = $options['from_name'] ?? '';
        $replyTo = $options['reply_to'] ?? $fromEmail;

        $socket = $this->connect();
        $this->expect($socket, 220);
        $this->command($socket, 'EHLO ' . $this->getHostname(), 250);

        if ($this->encryption === 'tls') {
            $this->command($socket, 'STARTTLS', 220);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Unable to enable TLS.');
            }
            $this->command($socket, 'EHLO ' . $this->getHostname(), 250);
        }

        if ($this->username !== '' && $this->password !== '') {
            $this->command($socket, 'AUTH LOGIN', 334);
            $this->command($socket, base64_encode($this->username), 334);
            $this->command($socket, base64_encode($this->password), 235);
        }

        $this->command($socket, 'MAIL FROM:<' . $fromEmail . '>', 250);
        $this->command($socket, 'RCPT TO:<' . $to . '>', 250);
        $this->command($socket, 'DATA', 354);

        $fromHeader = $fromName !== ''
            ? $this->encodeHeader($fromName) . ' <' . $fromEmail . '>'
            : $fromEmail;

        $headers = [
            'Date: ' . date('r'),
            'From: ' . $fromHeader,
            'To: ' . $to,
            'Reply-To: ' . $replyTo,
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $this->normalizeBody($body) . "\r\n.";
        $this->command($socket, $message, 250);
        $this->command($socket, 'QUIT', 221);
        fclose($socket);
    }

    private function connect()
    {
        $remote = match ($this->encryption) {
            'ssl' => 'ssl://' . $this->host . ':' . $this->port,
            default => 'tcp://' . $this->host . ':' . $this->port,
        };

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $socket = @stream_socket_client(
            $remote,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            $details = trim($errstr) !== '' ? $errstr : 'unable to connect to ' . $remote;
            throw new RuntimeException('SMTP connection failed (' . $errno . '): ' . $details);
        }

        stream_set_timeout($socket, $this->timeout);

        return $socket;
    }

    private function command($socket, string $command, int $expectedCode): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $expectedCode);
    }

    private function expect($socket, int $expectedCode): void
    {
        $response = '';

        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new RuntimeException(trim($response));
        }
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function normalizeBody(string $body): string
    {
        return preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $body));
    }

    private function getHostname(): string
    {
        return $_SERVER['SERVER_NAME'] ?? 'localhost';
    }
}
