<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;

test('storage_url returns null for empty path', function (): void {
    expect(storage_url(null))->toBeNull()
        ->and(storage_url(''))->toBeNull();
});

test('storage_url passes through absolute http URLs', function (): void {
    expect(storage_url('https://cdn.example.com/a.png'))->toBe('https://cdn.example.com/a.png')
        ->and(storage_url('http://example.com/b.png'))->toBe('http://example.com/b.png');
});

test('storage_url builds public disk URL for relative paths', function (): void {
    $expected = Storage::disk('public')->url('catalogs/file.pdf');

    expect(storage_url('catalogs/file.pdf'))->toBe($expected)
        ->and(storage_url('/catalogs/file.pdf'))->toBe($expected);
});
