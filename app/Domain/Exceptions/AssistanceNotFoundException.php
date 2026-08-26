<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use RuntimeException;

final class AssistanceNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Assistance record not found.');
    }
}
