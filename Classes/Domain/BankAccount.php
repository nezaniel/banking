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
use Nezaniel\Banking\Domain\Accounting\BankAccountWasClosed;
use Nezaniel\Banking\Domain\Accounting\BankAccountWasOpened;
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

    public function transferMoney(BankAccountNumber $to, MonetaryAmount $amount): void
    {
        if (!$this->getAccountOverdraftLimit()->covers($this->getBalance()->subtract($amount))) {
            throw new \DomainException('Given amount exceeds the account\'s overdraft limit', 1707258982);
        }
        $this->requireAccountToExist($to);

        $event = new MoneyWasTransferred($to, $amount, TransactionDate::now());
        $this->eventStore->commit(
            BankAccountEventStreamNameFactory::create($this->number),
            BankingEventNormalizer::normalizeEvent($event),
            ExpectedVersion::ANY()
        );
        $this->eventStore->commit(
            BankAccountEventStreamNameFactory::create($to),
            BankingEventNormalizer::normalizeEvent($event),
            ExpectedVersion::ANY()
        );
    }

    /**
     * @return array<int,BankingEventContract>
     */
    public function getEvents(): array
    {
        return array_map(
            fn (EventEnvelope $eventEnvelope): BankingEventContract => BankingEventNormalizer::denormalizeEvent($eventEnvelope->event),
            iterator_to_array($this->eventStore->load(BankAccountEventStreamNameFactory::create($this->number)))
        );
    }

    private function requireAccountToExist(BankAccountNumber $accountNumber): void
    {
        $accountExists = false;
        foreach ($this->eventStore->load(BankAccountEventStreamNameFactory::create($accountNumber)) as $eventEnvelope) {
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
