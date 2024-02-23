<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain\Accounting;

use Neos\Flow\Annotations as Flow;
use Nezaniel\Banking\Domain\BankAccountNumber;
use Nezaniel\Banking\Domain\BankingEventContract;
use Nezaniel\Banking\Domain\TransactionDate;

#[Flow\Proxy(false)]
final readonly class BankAccountWasUnblocked implements BankingEventContract
{
    public function __construct(
        public BankAccountNumber $accountNumber,
        public TransactionDate $date,
        public ?string $reason,
    ) {
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values): static
    {
        return new self(
            new BankAccountNumber($values['accountNumber']),
            new TransactionDate($values['date']),
            $values['reason'] ?? null,
        );
    }

    public static function fromCommand(UnblockBankAccount $command): self
    {
        return new self(
            $command->accountNumber,
            TransactionDate::now(),
            $command->reason
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
