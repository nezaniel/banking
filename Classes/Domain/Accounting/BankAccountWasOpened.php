<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain\Accounting;

use Neos\Flow\Annotations as Flow;
use Nezaniel\Banking\Domain\BankAccountId;
use Nezaniel\Banking\Domain\TransactionDate;

#[Flow\Proxy(false)]
final readonly class BankAccountWasOpened implements \JsonSerializable
{
    public function __construct(
        public BankAccountId $id,
        public TransactionDate $date,
    ) {
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            new BankAccountId($values['id']),
            new TransactionDate($values['date'])
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
