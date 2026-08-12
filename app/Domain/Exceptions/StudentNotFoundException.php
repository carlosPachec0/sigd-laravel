<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use RuntimeException;

final class StudentNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Student not found.');
    }
}
