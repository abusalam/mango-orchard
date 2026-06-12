<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Detects the server's real upload ceiling (php.ini) so upload guides can
 * advertise an honest per-file maximum and the client-side guard can warn
 * before a doomed upload starts.
 *
 * The effective limit for a single file is the SMALLEST of:
 *   - the app's validation rule for that form (passed in as KB)
 *   - upload_max_filesize (per-file PHP cap)
 *   - post_max_size (whole-request PHP cap — also bounds a single file)
 */
class UploadLimits
{
    private static ?int $serverMaxCache = null;

    /** Smallest php.ini upload-related cap, in bytes. */
    public static function serverMaxBytes(): int
    {
        if (self::$serverMaxCache !== null) {
            return self::$serverMaxCache;
        }

        $upload = self::parseIniSize((string) ini_get('upload_max_filesize'));
        $post = self::parseIniSize((string) ini_get('post_max_size'));

        // "0" means unlimited for post_max_size; treat as no constraint.
        $candidates = array_filter([$upload, $post], fn (int $v) => $v > 0);

        return self::$serverMaxCache = ($candidates === [] ? PHP_INT_MAX : min($candidates));
    }

    /** Effective per-file cap for a form whose validation rule is $maxKb. */
    public static function effectiveBytes(int $maxKb): int
    {
        return min($maxKb * 1024, self::serverMaxBytes());
    }

    /** True when php.ini, not the app rule, is the binding constraint. */
    public static function serverConstrains(int $maxKb): bool
    {
        return self::serverMaxBytes() < $maxKb * 1024;
    }

    /** "2 MB", "1.5 MB", "750 KB" — human-readable, no false precision. */
    public static function format(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            $mb = $bytes / (1024 * 1024);

            return (fmod($mb, 1.0) === 0.0 ? number_format($mb) : number_format($mb, 1)).' MB';
        }

        return number_format($bytes / 1024).' KB';
    }

    /** Parses php.ini shorthand ("100M", "2G", "512K", "1048576"). */
    public static function parseIniSize(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return 0; // unlimited / unset
        }

        $unit = strtoupper(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'G' => $number * 1024 * 1024 * 1024,
            'M' => $number * 1024 * 1024,
            'K' => $number * 1024,
            default => $number,
        };
    }
}
