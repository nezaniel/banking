<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final readonly class AccountOverdraftLimit implements \JsonSerializable
{
    public function __construct(
        public MonetaryAmount $amount,
    ) {
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            MonetaryAmount::fromArray($values['amount']),
        );
    }

    public static function zero(Currency $currency): self
    {
        return new self(
            MonetaryAmount::zero($currency)
        );
    }

    public function covers(MonetaryAmount $transactionResult): bool
    {
        return $transactionResult->exceeds(new MonetaryAmount(-$this->amount->value, $this->amount->currency));
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
