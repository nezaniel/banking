<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain\MoneyTransfer;

use Neos\Flow\Annotations as Flow;
use Nezaniel\Banking\Domain\MoneyTransfer\TransferMoney;
use Nezaniel\Banking\Domain\BankAccountNumber;
use Nezaniel\Banking\Domain\BankingEventContract;
use Nezaniel\Banking\Domain\MonetaryAmount;
use Nezaniel\Banking\Domain\TransactionDate;

#[Flow\Proxy(false)]
final readonly class MoneyWasTransferred implements BankingEventContract
{
    public function __construct(
        public BankAccountNumber $from,
        public BankAccountNumber $to,
        public MonetaryAmount $amount,
        public TransactionDate $startTime,
    ) {
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values): static
    {
        return new self(
            new BankAccountNumber($values['from']),
            new BankAccountNumber($values['to']),
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
