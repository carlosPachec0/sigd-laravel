<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use RuntimeException;

final class InvalidVerificationLinkException extends RuntimeException
{
    public function __construct(string $message = 'This verification link is invalid.')
    {
        parent::__construct($message);
    }
}
