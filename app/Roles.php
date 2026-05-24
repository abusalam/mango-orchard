<?php

declare(strict_types=1);

namespace App;

final class Roles
{
    public const string SUPERUSER = 'superuser';
    public const string EDITOR = 'editor';
    public const string VIEWER = 'viewer';

    public const array ALL = [
        self::SUPERUSER,
        self::EDITOR,
        self::VIEWER,
    ];
}
