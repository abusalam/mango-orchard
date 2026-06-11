<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Version tag for the footer + admin /system page.
 *
 * Composes "v{semver} · {short-commit}" where:
 *
 *   - semver comes from config('app.version'); bump on real releases
 *   - short-commit comes from config('app.commit') (set by CI/CD on
 *     deploy, e.g. via `APP_COMMIT=$(git rev-parse HEAD)`), with a
 *     fallback to reading `.git/HEAD` for local dev where the git
 *     directory is present
 *   - if neither is available the short-commit reads "dev"
 *
 * Cached per-request — the .git read happens at most once.
 */
class Version
{
    private static ?string $tagCache = null;

    private static ?string $shortCache = null;

    public static function tag(): string
    {
        if (self::$tagCache !== null) {
            return self::$tagCache;
        }

        $version = (string) (config('app.version') ?? '0.0.0');

        return self::$tagCache = 'v'.$version.' · '.self::shortCommit();
    }

    public static function shortCommit(): string
    {
        if (self::$shortCache !== null) {
            return self::$shortCache;
        }

        // 1. Config / env (set on deploy — recommended for prod where
        //    .git is not shipped).
        if ($commit = (string) (config('app.commit') ?? '')) {
            return self::$shortCache = substr($commit, 0, 7);
        }

        // 2. Read .git/HEAD for local dev.
        $head = base_path('.git/HEAD');
        if (! is_file($head)) {
            return self::$shortCache = 'dev';
        }

        $contents = trim((string) @file_get_contents($head));

        // Symbolic ref ("ref: refs/heads/main") — follow to the SHA.
        if (str_starts_with($contents, 'ref: ')) {
            $ref = substr($contents, 5);

            $refFile = base_path('.git/'.$ref);
            if (is_file($refFile)) {
                $sha = trim((string) @file_get_contents($refFile));
                if ($sha !== '') {
                    return self::$shortCache = substr($sha, 0, 7);
                }
            }

            // packed-refs fallback (some clones pack refs after gc).
            $packed = base_path('.git/packed-refs');
            if (is_file($packed)) {
                foreach (file($packed, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
                    if (str_ends_with($line, ' '.$ref)) {
                        $sha = explode(' ', $line, 2)[0] ?? '';
                        if ($sha !== '') {
                            return self::$shortCache = substr($sha, 0, 7);
                        }
                    }
                }
            }

            return self::$shortCache = 'dev';
        }

        // Detached HEAD case — HEAD already holds the SHA.
        return self::$shortCache = $contents !== ''
            ? substr($contents, 0, 7)
            : 'dev';
    }
}
