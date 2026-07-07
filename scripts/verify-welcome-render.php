<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$html = app(App\Services\Mailing\MailTemplateRenderer::class)->render('welcome', ['firstName' => 'Sarah']);

$checks = [
    public_url('images/mail/sourcenest-logo.png'),
    'Dear Sarah,',
    'How SourceNest',
    'What you can do',
    'Your first',
    'Explore the Platform',
    'Browse the supplier directory',
    'Unsubscribe',
    'Global Sourcing Platform',
    'Direct Access',
    'Pro Tip',
];

foreach ($checks as $check) {
    echo $check.': '.(str_contains($html, $check) ? 'yes' : 'no').PHP_EOL;
}
