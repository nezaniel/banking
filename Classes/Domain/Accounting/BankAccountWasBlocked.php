<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain\Accounting;

use Neos\Flow\Annotations as Flow;
use Nezaniel\Banking\Domain\BankAccountNumber;
use Nezaniel\Banking\Domain\TransactionDate;

#[Flow\Proxy(false)]
final readonly class BankAccountWasBlocked implements \JsonSerializable
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
    public static function fromArray(array $values): self
    {
        return new self(
            new BankAccountNumber($values['accountNumber']),
            new TransactionDate($values['date']),
            $values['reason'] ?? null,
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
