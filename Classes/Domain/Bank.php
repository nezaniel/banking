<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain;

use Neos\EventStore\DoctrineAdapter\DoctrineEventStore;
use Neos\EventStore\EventStoreInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\ConnectionFactory;
use Nezaniel\Banking\Domain\Accounting\BankAccountWasClosed;
use Nezaniel\Banking\Domain\Accounting\BankAccountWasOpened;

#[Flow\Scope('singleton')]
final readonly class Bank
{
    public function __construct(
        private Currency $currency,
        private ConnectionFactory $connectionFactory,
        private string $eventTableName,
    ) {
    }

    public function findAccount(BankAccountId $accountId): BankAccount
    {
        $eventStore = new DoctrineEventStore(
            $this->connectionFactory->create(),
            $this->eventTableName
        );
        $this->requireAccountToExist($accountId, $eventStore);

        return new BankAccount(
            $accountId,
            $this->currency,
            $eventStore
        );
    }

    private function requireAccountToExist(BankAccountId $accountId, EventStoreInterface $eventStore): void
    {
        $accountExists = false;
        foreach ($eventStore->load(BankAccountEventStreamNameFactory::create($accountId)) as $eventEnvelope) {
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
