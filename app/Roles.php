<?php

declare(strict_types=1);

namespace App;

use App\Models\User;

final class Roles
{
    public const string SUPERUSER = 'superuser';

    public const string CURATOR = 'curator';

    public const string VIEWER = 'viewer';

    public const string GROWER = 'grower';

    public const string IMPERSONATOR = 'impersonator';

    public const string CONVENER = 'convener';

    public const string ADVISOR = 'advisor';

    public const string MONITOR = 'monitor';

    public const string MONITOR_ADMIN = 'monitor-admin';

    public const string MANGO_ORCHARD_MEMBER = 'mango-orchard-member';

    public const array ALL = [
        self::SUPERUSER,
        self::CURATOR,
        self::VIEWER,
        self::GROWER,
        self::IMPERSONATOR,
        self::CONVENER,
        self::ADVISOR,
        self::MONITOR,
        self::MONITOR_ADMIN,
        self::MANGO_ORCHARD_MEMBER,
    ];

    /**
     * Modules and their gating "membership" role + the sub-roles a member
     * may self-apply for once enrolled.
     *
     * - Mango Orchard: `mango-orchard-member` unlocks self-apply for grower /
     *   curator / convener / advisor.
     * - Scheme Monitoring: `monitor` is the membership role and has no
     *   self-applicable sub-roles (monitor-admin is admin-only).
     *
     * @return array<string, array{membership: string, subRoles: list<string>}>
     */
    public static function modules(): array
    {
        return [
            'mango-orchard' => [
                'membership' => self::MANGO_ORCHARD_MEMBER,
                'subRoles' => [self::GROWER, self::CURATOR, self::CONVENER, self::ADVISOR],
            ],
            'scheme-monitoring' => [
                'membership' => self::MONITOR,
                'subRoles' => [],
            ],
        ];
    }

    /**
     * Roles that are never self-applicable. Membership roles for each
     * module are listed here because joining a module is an admin-only
     * enrolment.
     *
     * @return list<string>
     */
    public static function nonApplicable(): array
    {
        return [
            self::SUPERUSER,
            self::IMPERSONATOR,
            self::MONITOR,
            self::MONITOR_ADMIN,
            self::MANGO_ORCHARD_MEMBER,
        ];
    }

    /**
     * Roles inherently delegatable peer-to-peer. Module SUB-roles only —
     * never membership or privileged primitives. The recipient also has to
     * already be in the relevant module (enforced at submit time, see
     * StoreRoleDelegationRequest), so peer delegation can never put a
     * user into a module they haven't been enrolled in.
     *
     * @return list<string>
     */
    public static function delegatable(): array
    {
        return [self::GROWER, self::CURATOR, self::CONVENER, self::ADVISOR];
    }

    /**
     * Roles the given user can self-apply for right now. A user must hold
     * a module's membership role before any of that module's sub-roles
     * become applicable.
     *
     * @return list<string>
     */
    public static function applicableTo(User $user): array
    {
        $applicable = array_values(array_diff(self::ALL, self::nonApplicable()));

        foreach (self::modules() as $module) {
            if (! $user->hasRole($module['membership'])) {
                $applicable = array_values(array_diff($applicable, $module['subRoles']));
            }
        }

        return $applicable;
    }

    /**
     * Which module (if any) does this role belong to? Returns null for
     * cross-module roles like viewer or for privileged primitives.
     */
    public static function moduleFor(string $roleName): ?string
    {
        foreach (self::modules() as $key => $module) {
            if ($module['membership'] === $roleName || in_array($roleName, $module['subRoles'], true)) {
                return $key;
            }
        }

        return null;
    }
}
