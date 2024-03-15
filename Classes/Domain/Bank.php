<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain;

use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\EventStore\Model\EventStream\VirtualStreamName;
use Neos\Flow\Annotations as Flow;
use Nezaniel\Banking\Domain\Accounting\BankAccountOverdraftLimitWasSet;
use Nezaniel\Banking\Domain\Accounting\BankAccountWasBlocked;
use Nezaniel\Banking\Domain\Accounting\BankAccountWasClosed;
use Nezaniel\Banking\Domain\Accounting\BankAccountWasOpened;
use Nezaniel\Banking\Domain\Accounting\BankAccountWasUnblocked;
use Nezaniel\Banking\Domain\Accounting\BlockBankAccount;
use Nezaniel\Banking\Domain\Accounting\CloseBankAccount;
use Nezaniel\Banking\Domain\Accounting\OpenBankAccount;
use Nezaniel\Banking\Domain\Accounting\SetBankAccountOverdraftLimit;
use Nezaniel\Banking\Domain\Accounting\UnblockBankAccount;

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

    /**
     * @return iterable<BankingEventContract>
     */
    public function findEvents(StreamName|VirtualStreamName $streamName): iterable
    {
        foreach ($this->eventStore->load($streamName) as $eventEnvelope) {
            yield BankingEventNormalizer::denormalizeEvent($eventEnvelope->event);
        }
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
        foreach ($this->findEvents(VirtualStreamName::all()) as $event) {
            if ($event instanceof BankAccountWasOpened) {
                $availableAccountNumbers[$event->accountNumber->value] = $event->accountNumber;
            } elseif ($event instanceof BankAccountWasClosed) {
                unset($availableAccountNumbers[$event->accountNumber->value]);
            }
        }

        return array_map(
            fn (BankAccountNumber $accountNumber): BankAccount => $this->findAccount($accountNumber),
            array_values($availableAccountNumbers)
        );
    }

    public function openAccount(OpenBankAccount $command): void
    {
        $this->requireAccountToHaveNeverExisted($command->accountNumber);

        $this->eventStore->commit(
            $command->accountNumber->toStreamName(),
            BankingEventNormalizer::normalizeEvent(BankAccountWasOpened::fromCommand($command)),
            ExpectedVersion::ANY()
        );
    }

    public function setAccountOverdraftLimit(SetBankAccountOverdraftLimit $command): void
    {
        $this->requireAccountToExist($command->accountNumber);

        $this->eventStore->commit(
            $command->accountNumber->toStreamName(),
            BankingEventNormalizer::normalizeEvent(BankAccountOverdraftLimitWasSet::fromCommand($command)),
            ExpectedVersion::ANY()
        );
    }

    public function blockAccount(BlockBankAccount $command): void
    {
        $this->requireAccountToExist($command->accountNumber);
        $this->requireAccountToNotBeTheBanksOwn($command->accountNumber);
        $this->requireAccountToNotBeBlocked($command->accountNumber);

        $this->eventStore->commit(
            $command->accountNumber->toStreamName(),
            BankingEventNormalizer::normalizeEvent(BankAccountWasBlocked::fromCommand($command)),
            ExpectedVersion::ANY()
        );
    }

    public function unblockAccount(UnblockBankAccount $command): void
    {
        $this->requireAccountToExist($command->accountNumber);
        $this->requireAccountToBeBlocked($command->accountNumber);

        $this->eventStore->commit(
            $command->accountNumber->toStreamName(),
            BankingEventNormalizer::normalizeEvent(BankAccountWasUnblocked::fromCommand($command)),
            ExpectedVersion::ANY()
        );
    }

    public function closeAccount(CloseBankAccount $command): void
    {
        $this->requireAccountToExist($command->accountNumber);
        $this->requireAccountToNotBeTheBanksOwn($command->accountNumber);

        $this->eventStore->commit(
            $command->accountNumber->toStreamName(),
            BankingEventNormalizer::normalizeEvent(BankAccountWasClosed::fromCommand($command)),
            ExpectedVersion::ANY()
        );
    }

    private function requireAccountToExist(BankAccountNumber $accountNumber): void
    {
        $accountExists = false;
        foreach ($this->findEvents($accountNumber->toStreamName()) as $event) {
            if ($event instanceof BankAccountWasOpened) {
                $accountExists = true;
            } elseif ($event instanceof BankAccountWasClosed) {
                $accountExists = false;
            }
        }

        if (!$accountExists) {
            throw new \DomainException('Given account does not exist', 1707259253);
        }
    }

    private function requireAccountToHaveNeverExisted(BankAccountNumber $accountNumber): void
    {
        foreach ($this->findEvents($accountNumber->toStreamName()) as $event) {
            if ($event instanceof BankAccountWasOpened) {
                throw new \DomainException('Given account does already exist', 1708636166);
            }
        }
    }

    private function requireAccountToNotBeTheBanksOwn(BankAccountNumber $accountNumber): void
    {
        if ($accountNumber->equals($this->id)) {
            throw new \DomainException('Given account is the bank\'s own', 1708702933);
        }
    }

    private function requireAccountToNotBeBlocked(BankAccountNumber $accountNumber): void
    {
        if ($this->isAccountBlocked($accountNumber)) {
            throw new \DomainException('Given account is blocked', 1708638508);
        }
    }

    private function requireAccountToBeBlocked(BankAccountNumber $accountNumber): void
    {
        if (!$this->isAccountBlocked($accountNumber)) {
            throw new \DomainException('Given account is not blocked', 1708638508);
        }
    }

    private function isAccountBlocked(BankAccountNumber $accountNumber): bool
    {
        $accountIsBlocked = false;
        foreach ($this->findEvents($accountNumber->toStreamName()) as $event) {
            if ($event instanceof BankAccountWasBlocked) {
                $accountIsBlocked = true;
            } elseif ($event instanceof BankAccountWasUnblocked) {
                $accountIsBlocked = false;
            }
        }

        return $accountIsBlocked;
    }
}
