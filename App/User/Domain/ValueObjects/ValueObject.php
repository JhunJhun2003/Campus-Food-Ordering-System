<?php
namespace App\User\Domain\ValueObjects;

abstract class ValueObject
{
    abstract public function getValue();
    abstract public function equals(self $other): bool;
}