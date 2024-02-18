<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain\Accounting;

use Neos\Flow\Annotations as Flow;
use Nezaniel\Banking\Domain\AccountOverdraftLimit;
use Nezaniel\Banking\Domain\BankAccountNumber;

#[Flow\Proxy(false)]
final readonly class BankAccountOverdraftLimitWasSet implements \JsonSerializable
{
    public function __construct(
        public BankAccountNumber $accountNumber,
        public AccountOverdraftLimit $accountOverdraftLimit,
    ) {
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            new BankAccountNumber($values['accountNumber']),
            AccountOverdraftLimit::fromArray($values['accountOverdraftLimit']),
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
