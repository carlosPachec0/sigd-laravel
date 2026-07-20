<?php

declare(strict_types=1);

namespace App\Domain\Constants;

final class UserRoles
{
    public const string ADMIN = 'Admin';

    public const string STANDARD = 'Standard';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::ADMIN,
            self::STANDARD,
        ];
    }
}
