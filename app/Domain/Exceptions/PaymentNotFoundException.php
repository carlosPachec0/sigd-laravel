<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use RuntimeException;

final class PaymentNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Payment not found.');
    }
}
