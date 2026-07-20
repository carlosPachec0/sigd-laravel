<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use RuntimeException;

final class UserAlreadyExistsException extends RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct("A user with email '{$email}' already exists.");
    }
}
