<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain;

use Neos\EventStore\Model\Event\StreamName;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final readonly class BankAccountEventStreamNameFactory
{
    public static function create(BankAccountId $accountId): StreamName
    {
        return StreamName::fromString('Nezaniel.Banking:Bankaccount:' . $accountId->value);
    }
}
