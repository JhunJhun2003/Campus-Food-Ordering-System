<?php
namespace App\User\Domain\ValueObjects;

use App\User\Domain\ValueObjects\ValueObject;
use InvalidArgumentException;

class Email extends ValueObject
{
    private string $value;

    public function __construct(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address');
        }
        $this->value = strtolower(trim($email));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->value === $other->getValue();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}