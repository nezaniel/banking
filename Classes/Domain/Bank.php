<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain;

use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Neos\Flow\Annotations as Flow;
use Nezaniel\Banking\Domain\Accounting\BankAccountWasClosed;
use Nezaniel\Banking\Domain\Accounting\BankAccountWasOpened;

#[Flow\Proxy(false)]
final readonly class Bank
{
    public function __construct(
        public string $id,
        public Currency $currency,
        private EventStoreInterface $eventStore,
    ) {
    }

    public function setUp(): void
    {
        $this->eventStore->setup();
    }

    public function findAccount(BankAccountNumber $accountNumber): BankAccount
    {
        $this->requireAccountToExist($accountNumber);

        return new BankAccount(
            $accountNumber,
            $this->currency,
            $this->eventStore
        );
    }

    /**
     * @return array<BankAccount>
     */
    public function findAllAccounts(): array
    {
        $availableAccountNumbers = [];
        foreach ($this->eventStore->load(VirtualStreamName::all()) as $eventEnvelope) {
            $event = BankingEventNormalizer::denormalizeEvent($eventEnvelope->event);
            if ($event instanceof BankAccountWasOpened) {
                $availableAccountNumbers[$event->accountNumber->value] = $event->accountNumber;
            } elseif ($event instanceof BankAccountWasClosed) {
                unset($availableAccountNumbers[$event->accountNumber->value]);
            }
        }

        return array_map(
            fn (BankAccountNumber $accountNumber): BankAccount => $this->findAccount($accountNumber),
            $availableAccountNumbers
        );
    }

    private function requireAccountToExist(BankAccountNumber $accountId): void
    {
        $accountExists = false;
        foreach ($this->eventStore->load(BankAccountEventStreamNameFactory::create($accountId)) as $eventEnvelope) {
            if ($eventEnvelope->event instanceof BankAccountWasOpened) {
                $accountExists = true;
            } elseif ($eventEnvelope->event instanceof BankAccountWasClosed) {
                $accountExists = false;
            }
        }

        if (!$accountExists) {
            throw new \DomainException('Given account does not exist', 1707259253);
        }
    }
}
