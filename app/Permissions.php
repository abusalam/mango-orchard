<?php

declare(strict_types=1);

namespace App;

final class Permissions
{
    public const string VARIETIES_MANAGE = 'varieties.manage';
    public const string USERS_MANAGE = 'users.manage';
    public const string ROLES_MANAGE = 'roles.manage';
    public const string SETTINGS_MANAGE = 'settings.manage';

    public const array ALL = [
        self::VARIETIES_MANAGE => 'Create, edit, and delete mango varieties',
        self::USERS_MANAGE => 'View users and assign roles',
        self::ROLES_MANAGE => 'Create, edit, and delete roles and their permissions',
        self::SETTINGS_MANAGE => 'Change application-wide settings (e.g. captcha)',
    ];
}
