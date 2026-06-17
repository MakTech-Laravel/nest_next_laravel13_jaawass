<?php

use App\Enums\MailTemplate;
use App\Jobs\SendMailJob;
use App\Services\Mailing\MailgunTransport;
use App\Services\Mailing\MailingService;
use App\Services\Mailing\MailTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('mailing service dispatches send mail job with incremental delay', function () {
    Queue::fake();
    Cache::flush();

    $mailingService = app(MailingService::class);

    $mailingService->send('one@example.com', MailTemplate::Welcome, ['firstName' => 'One']);
    $mailingService->send('two@example.com', MailTemplate::PasswordResetOtp, ['otp' => '123456']);

    Queue::assertPushed(SendMailJob::class, 2);

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job): bool {
        return $job->recipient === 'one@example.com'
            && $job->template === MailTemplate::Welcome->value
            && $job->data['firstName'] === 'One';
    });

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job): bool {
        return $job->recipient === 'two@example.com'
            && $job->template === MailTemplate::PasswordResetOtp->value
            && $job->data['otp'] === '123456';
    });

    expect((int) Cache::get(config('mailing.dispatch_sequence_cache_key')))->toBe(2);
});

test('send mail job renders template and sends through transport', function () {
    $transport = Mockery::mock(MailgunTransport::class);
    $transport->shouldReceive('send')
        ->once()
        ->withArgs(function (string $recipient, string $subject, string $html): bool {
            return $recipient === 'user@example.com'
                && $subject === __('mail.password_reset_otp.subject')
                && str_contains($html, '123456');
        });

    $job = new SendMailJob('user@example.com', MailTemplate::PasswordResetOtp->value, ['otp' => '123456']);

    $job->handle(app(MailTemplateRenderer::class), $transport);
});

test('mail template renderer throws for unknown template', function () {
    app(MailTemplateRenderer::class)->render('does-not-exist', []);
})->throws(\App\Exceptions\Mailing\UnknownMailTemplateException::class);
