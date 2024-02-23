<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain;

use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\Flow\Annotations as Flow;
use Nezaniel\Banking\Domain\Accounting\BankAccountOverdraftLimitWasSet;
use Nezaniel\Banking\Domain\Accounting\BankAccountWasBlocked;
use Nezaniel\Banking\Domain\Accounting\BankAccountWasClosed;
use Nezaniel\Banking\Domain\Accounting\BankAccountWasOpened;
use Nezaniel\Banking\Domain\Accounting\BankAccountWasUnblocked;
use Nezaniel\Banking\Domain\MoneyTransfer\TransferMoney;
use Nezaniel\Banking\Domain\MoneyTransfer\MoneyWasTransferred;

#[Flow\Proxy(false)]
final readonly class BankAccount
{
    public function __construct(
        public BankAccountNumber $number,
        public Currency $currency,
        private EventStoreInterface $eventStore
    ) {
        $this->requireAccountToExist($number);
    }

    public function getHolder(): ?string
    {
        foreach ($this->getEvents() as $event) {
            if ($event instanceof BankAccountWasOpened) {
                return $event->holder;
            }
        }

        return null;
    }

    public function getAccountOverdraftLimit(): AccountOverdraftLimit
    {
        $accountOverdraftLimit = AccountOverdraftLimit::zero($this->currency);
        foreach ($this->getEvents() as $event) {
            if ($event instanceof BankAccountOverdraftLimitWasSet) {
                $accountOverdraftLimit = $event->accountOverdraftLimit;
            }
        }

        return $accountOverdraftLimit;
    }

    public function getBalance(): MonetaryAmount
    {
        $balance = MonetaryAmount::zero($this->currency);
        foreach ($this->getEvents() as $event) {
            if ($event instanceof MoneyWasTransferred) {
                if ($event->to->equals($this->number)) {
                    $balance = $balance->add($event->amount);
                } else {
                    $balance = $balance->subtract($event->amount);
                }
            }
        }

        return $balance;
    }

    public function isBlocked(): bool
    {
        $accountIsBlocked = false;
        foreach ($this->getEvents() as $event) {
            $accountIsBlocked = match (get_class($event)) {
                BankAccountWasBlocked::class => true,
                BankAccountWasUnblocked::class => false,
                default => $accountIsBlocked
            };
        }

        return $accountIsBlocked;
    }

    public function transferMoney(TransferMoney $command): void
    {
        $this->requireAccountToNotBeBlocked($this->number);
        $this->requireAccountToExist($command->to);
        $this->requireAccountToNotBeBlocked($command->to);
        if (!$this->getAccountOverdraftLimit()->covers($this->getBalance()->subtract($command->amount))) {
            throw new \DomainException('Given amount exceeds the account\'s overdraft limit', 1707258982);
        }

        $event = new MoneyWasTransferred($this->number, $command->to, $command->amount, TransactionDate::now());
        $this->eventStore->commit(
            $this->number->toStreamName(),
            BankingEventNormalizer::normalizeEvent($event),
            ExpectedVersion::ANY()
        );
        $this->eventStore->commit(
            $command->to->toStreamName(),
            BankingEventNormalizer::normalizeEvent($event),
            ExpectedVersion::ANY()
        );
    }

    /**
     * @return array<int,BankingEventContract>
     */
    public function getEvents(): array
    {
        return $this->getEventsForAccountNumber($this->number);
    }

    /**
     * @return array<int,BankingEventContract>
     */
    private function getEventsForAccountNumber(BankAccountNumber $accountNumber): array
    {
        return array_map(
            fn (EventEnvelope $eventEnvelope): BankingEventContract => BankingEventNormalizer::denormalizeEvent($eventEnvelope->event),
            iterator_to_array($this->eventStore->load($accountNumber->toStreamName()))
        );
    }

    private function requireAccountToExist(BankAccountNumber $accountNumber): void
    {
        $accountExists = false;
        foreach ($this->getEventsForAccountNumber($accountNumber) as $event) {
            $accountExists = match (get_class($event)) {
                BankAccountWasOpened::class => true,
                BankAccountWasClosed::class => false,
                default => $accountExists
            };
        }

        if (!$accountExists) {
            throw new \DomainException('Given account does not exist', 1707259253);
        }
    }

    private function requireAccountToNotBeBlocked(BankAccountNumber $accountNumber): void
    {
        $accountIsBlocked = false;
        foreach ($this->getEventsForAccountNumber($accountNumber) as $event) {
            $accountIsBlocked = match (get_class($event)) {
                BankAccountWasBlocked::class => true,
                BankAccountWasUnblocked::class => false,
                default => $accountIsBlocked
            };
        }

        if ($accountIsBlocked) {
            throw new \DomainException('Given account is blocked', 1708674826);
        }
    }
}
