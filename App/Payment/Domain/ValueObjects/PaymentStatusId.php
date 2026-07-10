<?php
declare(strict_types=1);

namespace App\Payment\Domain\ValueObjects;

class PaymentStatusId
{
    public const PENDING = 1;
    public const PAID = 2;
    public const FAILED = 3;
}   