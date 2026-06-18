<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$secret = (string) config('services.mailgun.secret');
$domain = (string) config('services.mailgun.domain');
$endpoint = (string) config('services.mailgun.endpoint');
$from = (string) config('mail.from.address');

echo 'domain: '.$domain.PHP_EOL;
echo 'endpoint: '.$endpoint.PHP_EOL;
echo 'from: '.$from.PHP_EOL;

$client = Mailgun\Mailgun::create($secret, $endpoint);
$domains = $client->domains()->index();

echo 'domains on account:'.PHP_EOL;
foreach ($domains->getDomains() as $item) {
    echo '  - '.$item->getName().PHP_EOL;
}

$recipient = $argv[1] ?? 'test@example.com';
echo PHP_EOL.'Attempting send to: '.$recipient.PHP_EOL;

try {
    $client->messages()->send($domain, [
        'from' => 'Jaawaas <'.$from.'>',
        'to' => $recipient,
        'subject' => 'Mailgun sandbox test',
        'text' => 'If you see this, Mailgun works.',
    ]);
    echo "OK: message accepted by Mailgun.\n";
} catch (Throwable $e) {
    echo 'SEND ERROR: '.$e->getMessage().PHP_EOL;
    exit(1);
}
