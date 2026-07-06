<?php

namespace App\User\Domain\ValueObjects;

class UserId extends ValueObject
{
    private int $value;

    public function __construct(?int $value = null)
    {
        $this->value = $value ?? 0;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return $this->value === 0;
    }

    public function equals(ValueObject $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }
        return $this->value === $other->getValue();
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}