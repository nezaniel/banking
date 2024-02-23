<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final readonly class TransactionDate implements \JsonSerializable
{
    public function __construct(
        public int $value,
    ) {
    }

    public static function now(): self
    {
        return new self(
            self::adjustDate(new \DateTimeImmutable('now'))->getTimestamp()
        );
    }

    private static function adjustDate(\DateTimeInterface $date): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($date)->setTimezone(new \DateTimeZone('UTC'));
    }

    public function format(string $format): string
    {
        return (new \DateTimeImmutable('@' . $this->value))->format($format);
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
