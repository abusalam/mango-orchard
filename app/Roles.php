<?php

declare(strict_types=1);

namespace App;

final class Roles
{
    public const string SUPERUSER = 'superuser';
    public const string EDITOR = 'editor';
    public const string VIEWER = 'viewer';
    public const string GROWER = 'grower';
    public const string IMPERSONATOR = 'impersonator';

    public const array ALL = [
        self::SUPERUSER,
        self::EDITOR,
        self::VIEWER,
        self::GROWER,
        self::IMPERSONATOR,
    ];

    /**
     * Roles users cannot self-apply for via the profile UI. Superuser is
     * reserved for the very first registrant; impersonator hands out an
     * escalation primitive (login-as-anyone) that must be admin-bestowed.
     *
     * @return list<string>
     */
    public static function nonApplicable(): array
    {
        return [self::SUPERUSER, self::IMPERSONATOR];
    }
}
