<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Domain;

use Neos\EventStore\DoctrineAdapter\DoctrineEventStore;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\ConnectionFactory;

#[Flow\Scope('singleton')]
class FinancialDistrict
{
    /**
     * @var array<string,array{currency: string, eventTableName: string}
     */
    #[Flow\InjectConfiguration(path: 'banks')]
    protected array $bankingConfig = [];

    #[Flow\Inject]
    protected ConnectionFactory $connectionFactory;

    public function findBankById(string $id): ?Bank
    {
        if (array_key_exists($id, $this->bankingConfig)) {
            $config = $this->bankingConfig[$id];

            return new Bank(
                $id,
                new Currency($config['currency']),
                new DoctrineEventStore(
                    $this->connectionFactory->create(),
                    $config['eventTableName']
                )
            );
        }

        return null;
    }

    /**
     * @return array<string, Bank>
     */
    public function findAllBanks(): array
    {
        $databaseConnection = $this->connectionFactory->create();

        return array_map(
            fn (array $config, string $id): Bank => new Bank(
                $id,
                new Currency($config['currency']),
                new DoctrineEventStore(
                    $databaseConnection,
                    $config['eventTableName']
                )
            ),
            $this->bankingConfig,
            array_keys($this->bankingConfig)
        );
    }
}
