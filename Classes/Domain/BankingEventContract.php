<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain;

interface BankingEventContract extends \JsonSerializable
{
    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(array $values): static;
}
