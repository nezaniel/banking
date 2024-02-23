<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final readonly class MonetaryAmount implements \JsonSerializable
{
    public function __construct(
        public int $value,
        public Currency $currency
    ) {
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            (int)$values['value'],
            new Currency($values['currency'])
        );
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }

    public function add(self $other): self
    {
        if (!$other->currency->equals($this->currency)) {
            throw new \DomainException('Cannot add monetary amounts of different currencies', 1707258606);
        }

        return new self($this->value + $other->value, $this->currency);
    }

    public function subtract(self $other): self
    {
        if (!$other->currency->equals($this->currency)) {
            throw new \DomainException('Cannot subtract monetary amounts of different currencies', 1707258662);
        }

        return new self($this->value - $other->value, $this->currency);
    }

    public function exceeds(self $other): bool
    {
        if (!$other->currency->equals($this->currency)) {
            throw new \DomainException('Cannot compare monetary amounts of different currencies', 1707258667);
        }

        return $this->value > $other->value;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
