<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain\MoneyTransfer;

use Neos\Flow\Annotations as Flow;
use Nezaniel\Banking\Domain\BankAccountNumber;
use Nezaniel\Banking\Domain\MonetaryAmount;

#[Flow\Proxy(false)]
final readonly class TransferMoney implements \JsonSerializable
{
    public function __construct(
        public BankAccountNumber $to,
        public MonetaryAmount $amount,
    ) {
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            new BankAccountNumber($values['to']),
            MonetaryAmount::fromArray($values['amount'])
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
