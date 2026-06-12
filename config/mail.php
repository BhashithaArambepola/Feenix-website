<?php

return [
    // cPanel on the same server: use PHP mail() first (see post.php).
    // SMTP fallbacks use localhost only — do not use feenixmarketing.com (Cloudflare blocks SMTP).
    'host' => 'localhost',
    'port' => 25,
    'encryption' => 'none',
    'username' => 'no-reply@feenixmarketing.com',
    'password' => 'M6X2BPKXgcvS&oyv',
    'from_email' => 'no-reply@feenixmarketing.com',
    'from_name' => 'Feenix Website',
    'to_email' => 'contact@feenixmarketing.com',
    'timeout' => 10,
    'debug' => false,
];
