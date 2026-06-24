<?php
namespace App\User\Domain\ValueObjects;

use App\User\Domain\ValueObjects\ValueObject;

class UserId extends ValueObject
{
    private ?int $value;

    public function __construct(?int $id = null)
    {
        $this->value = $id;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return $this->value === null;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->value === $other->getValue();
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}