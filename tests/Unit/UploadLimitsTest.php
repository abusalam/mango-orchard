<?php

declare(strict_types=1);

use App\Support\UploadLimits;

it('parses php.ini shorthand sizes', function (string $raw, int $expected) {
    expect(UploadLimits::parseIniSize($raw))->toBe($expected);
})->with([
    ['100M', 100 * 1024 * 1024],
    ['2G', 2 * 1024 * 1024 * 1024],
    ['512K', 512 * 1024],
    ['1048576', 1048576],
    ['0', 0],
    ['-1', 0],
    ['', 0],
]);

it('caps the effective limit at the server maximum', function () {
    $server = UploadLimits::serverMaxBytes();

    // An app rule far above any sane php.ini value gets clamped…
    expect(UploadLimits::effectiveBytes(PHP_INT_MAX >> 12))->toBe($server);

    // …while a tiny app rule passes through untouched.
    expect(UploadLimits::effectiveBytes(100))->toBe(100 * 1024);
});

it('formats byte counts without false precision', function () {
    expect(UploadLimits::format(2 * 1024 * 1024))->toBe('2 MB');
    expect(UploadLimits::format((int) (1.5 * 1024 * 1024)))->toBe('1.5 MB');
    expect(UploadLimits::format(750 * 1024))->toBe('750 KB');
});
