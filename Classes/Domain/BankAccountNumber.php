<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain;

use Neos\ContentRepository\Core\SharedModel\Id\UuidFactory;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final readonly class BankAccountNumber implements \JsonSerializable
{
    public function __construct(
        public string $value
    ) {
    }

    public static function createRandom(): self
    {
        return new self(UuidFactory::create());
    }

    public function equals(self|string $other): bool
    {
        return is_string($other) && $this->value === $other
            || $this->value === $other->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
