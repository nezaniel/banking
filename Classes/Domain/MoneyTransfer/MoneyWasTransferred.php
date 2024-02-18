<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain\MoneyTransfer;

use Neos\Flow\Annotations as Flow;
use Nezaniel\Banking\Domain\BankAccountId;
use Nezaniel\Banking\Domain\MonetaryAmount;
use Nezaniel\Banking\Domain\TransactionDate;

#[Flow\Proxy(false)]
final readonly class MoneyWasTransferred implements \JsonSerializable
{
    public function __construct(
        public BankAccountId $to,
        public MonetaryAmount $amount,
        public TransactionDate $startTime,
    ) {
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            new BankAccountId($values['to']),
            MonetaryAmount::fromArray($values['amount']),
            new TransactionDate($values['startTime'])
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
