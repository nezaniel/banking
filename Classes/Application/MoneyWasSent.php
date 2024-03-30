<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Application;

use Neos\Flow\Annotations as Flow;
use Sitegeist\SchemeOnYou\Domain\Metadata\Response;
use Sitegeist\SchemeOnYou\Domain\Metadata\Schema;

#[Flow\Proxy(false)]
#[Schema('The notification about that money was sent')]
#[Response(200, '')]
final readonly class MoneyWasSent
{
    public function __construct()
    {
    }
}
