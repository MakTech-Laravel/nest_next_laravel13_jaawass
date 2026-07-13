<?php

use App\Support\Localization\LocaleCode;

test('canonical normalizes hyphen and underscore variants', function () {
    expect(LocaleCode::canonical('zh-CN'))->toBe('zh_CN');
    expect(LocaleCode::canonical('zh_CN'))->toBe('zh_CN');
    expect(LocaleCode::canonical('ar'))->toBe('ar');
});

test('resolveSupported matches locale aliases', function () {
    $supported = ['en', 'ar', 'he', 'zh_CN'];

    expect(LocaleCode::resolveSupported('zh-CN', $supported))->toBe('zh_CN');
    expect(LocaleCode::resolveSupported('ar', $supported))->toBe('ar');
    // Legacy frontend Chinese slot
    expect(LocaleCode::resolveSupported('es', $supported))->toBe('zh_CN');
});

test('resolveSupported keeps es when spanish is a supported product locale', function () {
    $supported = ['en', 'es', 'zh_CN'];

    expect(LocaleCode::resolveSupported('es', $supported))->toBe('es');
});

test('toGoogle converts app locale to Google API code', function () {
    expect(LocaleCode::toGoogle('zh_CN'))->toBe('zh-CN');
    expect(LocaleCode::toGoogle('he'))->toBe('he');
});
