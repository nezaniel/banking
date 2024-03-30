<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Application;

use Neos\Flow\Annotations as Flow;
use Nezaniel\Banking\Domain\BankAccountNumber;
use Nezaniel\Banking\Domain\MonetaryAmount;
use Sitegeist\SchemeOnYou\Domain\Metadata\Schema;

#[Flow\Proxy(false)]
#[Schema('The command to write a log')]
final readonly class SendMoney
{
    public function __construct(
        public BankAccountNumber $from,
        public BankAccountNumber $to,
        public MonetaryAmount $amount,
    ) {
    }
}
