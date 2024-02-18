<?php

declare(strict_types=1);

namespace Nezaniel\Banking\Domain;

use Neos\EventStore\Model\Event\EventData;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Event\EventMetadata;
use Neos\EventStore\Model\Event\EventType;
use Nezaniel\Banking\Domain\MoneyTransfer\MoneyWasTransferred;

/**
 * Central authority to convert banking domain events to Event Store EventData and EventType, vice versa.
 *
 * @internal
 */
final class BankingEventNormalizer
{
    public static function getEventData(MoneyWasTransferred $event): EventData
    {
        return EventData::fromString(json_encode($event, JSON_THROW_ON_ERROR));
    }

    public static function normalizeEvent(MoneyWasTransferred $event): Event
    {
        return new Event(
            EventId::create(),
            self::getEventTypeFromEventClassName(get_class($event)),
            self::getEventData($event),
            EventMetadata::fromArray([]),
        );
    }

    public static function denormalizeEvent(Event $event): BankingEventContract
    {
        $eventDataAsArray = json_decode($event->data->value, true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($eventDataAsArray));

        /** @var class-string<BankingEventContract> $className */
        $className = self::getEventClassNameFromEventType($event->type);
        return $className::fromArray($eventDataAsArray);
    }

    /**
     * @return class-string
     */
    public static function getEventClassNameFromEventType(EventType $eventType): string
    {
        return match ($eventType->value) {
            'Nezaniel.Banking:MoneyWasTransferred' => MoneyWasTransferred::class,
            default => throw new \DomainException(
                'Cannot resolve event class name for unfamiliar event type "' . $eventType->value . '"',
                1707260500
            )
        };
    }

    /**
     * @param class-string $eventClassName
     */
    public static function getEventTypeFromEventClassName(string $eventClassName): EventType
    {
        return match ($eventClassName) {
            MoneyWasTransferred::class => EventType::fromString('Nezaniel.Banking:MoneyWasTransferred'),
            default => throw new \DomainException(
                'Cannot resolve event type for unfamiliar event class name "' . $eventClassName . '"',
                1707260379
            )
        };
    }
}
