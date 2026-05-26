<?php

declare(strict_types=1);

namespace App;

final class Permissions
{
    public const string VARIETIES_MANAGE = 'varieties.manage';

    public const string USERS_MANAGE = 'users.manage';

    public const string ROLES_MANAGE = 'roles.manage';

    public const string SETTINGS_MANAGE = 'settings.manage';

    public const string TELEMETRY_VIEW = 'telemetry.view';

    public const string LISTINGS_MANAGE = 'listings.manage';

    public const string USERS_IMPERSONATE = 'users.impersonate';

    public const string EVENTS_MANAGE = 'events.manage';

    public const string ADVISORIES_MANAGE = 'advisories.manage';

    public const array ALL = [
        self::VARIETIES_MANAGE => 'Create, edit, and delete mango varieties',
        self::USERS_MANAGE => 'View users and assign roles',
        self::ROLES_MANAGE => 'Create, edit, and delete roles and their permissions',
        self::SETTINGS_MANAGE => 'Change application-wide settings (e.g. captcha)',
        self::TELEMETRY_VIEW => 'View the activity / telemetry feed',
        self::LISTINGS_MANAGE => 'Post listings to the grower marketplace',
        self::USERS_IMPERSONATE => 'Log in as another user (or any user holding a given role) and switch back at will',
        self::EVENTS_MANAGE => 'Create, edit, and delete training/education events for orchard owners',
        self::ADVISORIES_MANAGE => 'Issue, edit, and retire orchard advisories (seasonal alerts, best practices, pest warnings)',
    ];
}
