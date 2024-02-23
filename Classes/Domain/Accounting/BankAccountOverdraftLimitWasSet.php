<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain\Accounting;

use Neos\Flow\Annotations as Flow;
use Nezaniel\Banking\Domain\AccountOverdraftLimit;
use Nezaniel\Banking\Domain\BankAccountNumber;
use Nezaniel\Banking\Domain\BankingEventContract;
use Nezaniel\Banking\Domain\TransactionDate;

#[Flow\Proxy(false)]
final readonly class BankAccountOverdraftLimitWasSet implements BankingEventContract
{
    public function __construct(
        public BankAccountNumber $accountNumber,
        public AccountOverdraftLimit $accountOverdraftLimit,
        public TransactionDate $date,
    ) {
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values): static
    {
        return new self(
            new BankAccountNumber($values['accountNumber']),
            AccountOverdraftLimit::fromArray($values['accountOverdraftLimit']),
            new TransactionDate($values['date']),
        );
    }

    public static function fromCommand(SetBankAccountOverdraftLimit $command): self
    {
        return new self(
            $command->accountNumber,
            $command->accountOverdraftLimit,
            TransactionDate::now()
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
