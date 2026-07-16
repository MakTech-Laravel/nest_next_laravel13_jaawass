<?php

require __DIR__.'/../vendor/autoload.php';

use App\Services\Mailing\MailTemplateRenderer;
use Illuminate\Support\Facades\View;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$renderer = app(MailTemplateRenderer::class);
$templates = config('mailing.templates', []);

$sampleData = [
    'firstName' => 'Jane',
    'name' => 'Jane Doe',
    'recipientName' => 'Jane Doe',
    'otp' => '123456',
    'formattedOtp' => '123 456',
    'ticketNumber' => 'TKT-1001',
    'referenceId' => 'REF-1001',
    'subject' => 'Sample support subject',
    'ticketSubject' => 'Need help with order',
    'orderNumber' => 'ORD-5001',
    'orderId' => 5001,
    'buyerName' => 'Jane Buyer',
    'supplierName' => 'Acme Manufacturing',
    'reportId' => 1001,
    'manufacturerName' => 'Acme Manufacturing',
    'manufacturerDisplayName' => 'Acme Manufacturing',
    'productName' => 'Steel Widget',
    'rfqNumber' => 'RFQ-2001',
    'planName' => 'Pro Plan',
    'failedAt' => 'July 16, 2026',
    'ctaUrl' => 'https://example.com/dashboard',
    'ctaLabel' => 'View Details',
    'intro' => 'Sample intro text.',
    'headerTitle' => 'Sample Title',
    'headerEyebrow' => 'Notification',
    'headerSubtitle' => 'Subtitle',
    'greeting' => 'Hello Jane,',
    'messageBody' => '<p>Sample message body.</p>',
    'messageHeading' => 'Details',
    'detailsHeading' => 'Summary',
    'details' => ['Order' => 'ORD-5001', 'Status' => 'Open'],
    'submittedDate' => 'July 16, 2026',
    'validUntil' => 'July 30, 2026',
    'device' => 'Chrome on Windows',
    'recipient_name' => 'Admin User',
    'platform_name' => 'SourceNest',
    'from_name' => 'SourceNest',
    'from_email' => 'noreply@sourcenest.com',
    'ttlMinutes' => 15,
    'expiresIn' => '15 minutes',
    'billingInterval' => 'Monthly',
    'startsAt' => 'July 1, 2026',
    'endsAt' => 'August 1, 2026',
    'paidAmount' => '$99.00',
    'reviewUrl' => 'https://example.com/admin/review',
    'responses' => [
        ['typeLabel' => 'Document', 'message' => 'Sample response message.', 'fileName' => 'certificate.pdf'],
    ],
    'adminName' => 'Admin User',
    'admin' => 'Admin User',
    'company' => 'Acme Co',
    'companyName' => 'Acme Co',
    'ticketSubject' => 'Need help with order',
    'statusLabel' => 'Under Review',
    'status' => 'In Production',
    'reasonLabel' => 'Misleading information',
    'reportsUrl' => 'https://example.com/reports',
    'daysRemaining' => 7,
    'plansUrl' => 'https://example.com/plans',
    'rating' => 5,
    'reviewText' => 'Great product!',
    'messagePreview' => 'Hello, following up on your request.',
    'senderName' => 'John Smith',
    'alertTag' => 'Alert',
    'alertHeading' => 'Important',
    'alertMeta' => 'Please review.',
    'extraBody' => 'Additional information here.',
    'footerNote' => 'Automated notification.',
    'expires' => 'Expires in 15 minutes.',
    'ignoreNotice' => 'If you did not request this, ignore this email.',
];

$extraViews = [
    'mail.auth.otp' => 'PasswordResetOtpMail (legacy mailable)',
];

$passed = 0;
$failed = [];
$checkedViews = [];

echo "=== Mail Template Verification ===\n\n";

foreach ($templates as $key => $definition) {
    $view = $definition['view'] ?? null;
    if (! is_string($view) || $view === '') {
        $failed[] = ['template' => $key, 'view' => '(missing)', 'error' => 'No view defined in config'];
        continue;
    }

    if (isset($checkedViews[$view])) {
        echo "SKIP  {$key} -> {$view} (already verified)\n";
        continue;
    }

    try {
        if (! View::exists($view)) {
            throw new RuntimeException("View does not exist: {$view}");
        }

        $html = $renderer->render($key, $sampleData);
        $subject = $renderer->subject($key, $sampleData);

        if (! is_string($html) || trim($html) === '') {
            throw new RuntimeException('Rendered HTML is empty');
        }

        if (! str_contains($html, '<!DOCTYPE html>') && ! str_contains($html, '<html')) {
            throw new RuntimeException('Rendered output does not look like HTML');
        }

        if (! is_string($subject) || trim($subject) === '') {
            throw new RuntimeException('Subject is empty');
        }

        $checkedViews[$view] = true;
        $passed++;
        echo "PASS  {$key}\n";
        echo "      view: {$view}\n";
        echo "      subject: {$subject}\n";
        echo "      html length: ".strlen($html)." bytes\n\n";
    } catch (Throwable $e) {
        $failed[] = ['template' => $key, 'view' => $view, 'error' => $e->getMessage()];
        echo "FAIL  {$key} -> {$view}\n";
        echo "      {$e->getMessage()}\n\n";
    }
}

foreach ($extraViews as $view => $label) {
    if (isset($checkedViews[$view])) {
        continue;
    }

    try {
        if (! View::exists($view)) {
            throw new RuntimeException("View does not exist: {$view}");
        }

        $html = View::make($view, array_merge($sampleData, [
            'subjectLine' => 'Sample OTP',
            'headerTitle' => 'Your code',
        ]))->render();

        if (trim($html) === '') {
            throw new RuntimeException('Rendered HTML is empty');
        }

        $checkedViews[$view] = true;
        $passed++;
        echo "PASS  {$label}\n";
        echo "      view: {$view}\n";
        echo "      html length: ".strlen($html)." bytes\n\n";
    } catch (Throwable $e) {
        $failed[] = ['template' => $label, 'view' => $view, 'error' => $e->getMessage()];
        echo "FAIL  {$label} -> {$view}\n";
        echo "      {$e->getMessage()}\n\n";
    }
}

$mailDir = resource_path('views/mail');
$bladeFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($mailDir, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($mailDir) + 1));
        $viewName = 'mail.'.str_replace(['/', '.blade.php'], ['.', ''], $relative);
        $bladeFiles[$viewName] = $relative;
    }
}

$orphaned = [];
foreach ($bladeFiles as $viewName => $relative) {
    if (! isset($checkedViews[$viewName])) {
        try {
            $html = View::make($viewName, $sampleData)->render();
            if (trim($html) !== '') {
                $checkedViews[$viewName] = true;
                $passed++;
                echo "PASS  (orphan blade) {$viewName}\n";
                echo "      file: {$relative}\n";
                echo "      html length: ".strlen($html)." bytes\n\n";
            }
        } catch (Throwable $e) {
            $orphaned[] = ['view' => $viewName, 'file' => $relative, 'error' => $e->getMessage()];
            echo "FAIL  (orphan blade) {$viewName}\n";
            echo "      {$e->getMessage()}\n\n";
        }
    }
}

echo "=== Summary ===\n";
echo "Passed: {$passed}\n";
echo 'Failed: '.count($failed)."\n";
echo 'Orphan render failures: '.count($orphaned)."\n";
echo 'Unique views checked: '.count($checkedViews)."\n";
echo 'Blade files on disk: '.count($bladeFiles)."\n";

if ($failed !== [] || $orphaned !== []) {
    echo "\nFailures:\n";
    foreach (array_merge($failed, $orphaned) as $item) {
        $template = $item['template'] ?? $item['view'];
        echo " - {$template}: {$item['error']}\n";
    }
    exit(1);
}

echo "\nAll mail templates verified successfully.\n";
exit(0);
