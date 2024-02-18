<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final readonly class Currency implements \JsonSerializable
{
    public function __construct(
        public string $value
    ) {
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
