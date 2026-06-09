<?php

use App\Services\Translation\GoogleTranslationService;
use Tests\TestCase;

uses(TestCase::class);
use Google\Cloud\Translate\V3\Client\TranslationServiceClient;

it('resolves the v3 TranslationServiceClient from google/cloud-translate', function () {
    expect(class_exists(TranslationServiceClient::class))->toBeTrue();
});

it('resolves /storage/... credential paths to Laravel storage_path', function () {
    $tempName = 'test-google-creds-'.uniqid('', true).'.json';
    $fullPath = storage_path($tempName);
    file_put_contents($fullPath, '{"type":"service_account"}');

    try {
        $method = new ReflectionMethod(GoogleTranslationService::class, 'resolveKeyFilePath');
        $method->setAccessible(true);
        $resolved = $method->invoke(null, '/storage/'.$tempName);

        expect($resolved)->toBe($fullPath)
            ->and(is_file($resolved))->toBeTrue();
    } finally {
        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }
});
