<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use RuntimeException;

final class InvalidPasswordResetTokenException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This password reset link is invalid or has expired.');
    }
}
