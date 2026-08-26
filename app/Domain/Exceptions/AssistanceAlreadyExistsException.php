<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use RuntimeException;

final class AssistanceAlreadyExistsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('An assistance record already exists for this student on this date.');
    }
}
