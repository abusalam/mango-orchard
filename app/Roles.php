<?php

declare(strict_types=1);

namespace App;

final class Roles
{
    public const string SUPERUSER = 'superuser';

    public const string CURATOR = 'curator';

    public const string VIEWER = 'viewer';

    public const string GROWER = 'grower';

    public const string IMPERSONATOR = 'impersonator';

    public const string CONVENER = 'convener';

    public const array ALL = [
        self::SUPERUSER,
        self::CURATOR,
        self::VIEWER,
        self::GROWER,
        self::IMPERSONATOR,
        self::CONVENER,
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

    /**
     * Roles a holder may delegate to other users without admin involvement.
     * Excludes anything that could be abused for privilege escalation —
     * superuser and impersonator never (an impersonator could mint more
     * impersonators in a loop). Viewer is informational-only so there's
     * nothing meaningful to grant.
     *
     * @return list<string>
     */
    public static function delegatable(): array
    {
        return [self::GROWER, self::CURATOR, self::CONVENER];
    }
}
