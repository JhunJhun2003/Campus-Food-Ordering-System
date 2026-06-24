<?php
namespace App\User\Domain\ValueObjects;

use App\User\Domain\ValueObjects\ValueObject;
use InvalidArgumentException;

class Password extends ValueObject
{
    private string $hashedValue;

    public function __construct(string $password, bool $isHashed = false)
    {
        if (!$isHashed) {
            $this->validate($password);
            $this->hashedValue = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $this->hashedValue = $password;
        }
    }

    private function validate(string $password): void
    {
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters');
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException('Password must contain at least one uppercase letter');
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidArgumentException('Password must contain at least one lowercase letter');
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException('Password must contain at least one number');
        }
    }

    public function verify(string $password): bool
    {
        return password_verify($password, $this->hashedValue);
    }

    public function getValue(): string
    {
        return $this->hashedValue;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->hashedValue === $other->getValue();
    }
}