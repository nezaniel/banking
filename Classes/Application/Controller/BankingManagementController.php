<?php

/*
 * This file is part of the Nezaniel.Banking package.
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Application\Controller;

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Nezaniel\Banking\Domain\Bank;
use Nezaniel\Banking\Domain\BankAccount;
use Nezaniel\Banking\Domain\FinancialDistrict;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Link\Link;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\Table;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\TableCell;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\TableCellAlignment;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\TableCells;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\TableHead;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\TableHeadSet;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\TableRow;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\TableRows;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Layout\ContentContainer\ContentContainer;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Layout\FluidRow\FluidRow;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Legend\Legend;
use Nezaniel\ComponentView\Domain\ComponentCollection;

#[Flow\Scope('singleton')]
class BankingManagementController extends AbstractModuleController
{
    #[Flow\Inject]
    protected FinancialDistrict $financialDistrict;

    public function indexAction(): string
    {
        $content = new ContentContainer(
            new FluidRow(
                new ComponentCollection(
                    new Legend('Available banks'),
                    new Table(
                        new TableRow(
                            new TableHeadSet(
                                new TableHead(TableCellAlignment::VARIANT_REGULAR, 'ID')
                            )
                        ),
                        new TableRows(
                            ...array_map(
                                fn (Bank $bank): TableRow => new TableRow(
                                    new TableCells(
                                        new TableCell(
                                            TableCellAlignment::VARIANT_REGULAR,
                                            null,
                                            new Link(
                                                $this->getActionUri(
                                                    actionName: 'show',
                                                    parameters: ['id' => $bank->id]
                                                ),
                                                $bank->id
                                            )
                                        )
                                    )
                                ),
                                $this->financialDistrict->findAllBanks()
                            )
                        )
                    )
                )
            )
        );

        return (string)$content;
    }

    public function showAction(string $id): string
    {
        $bank = $this->financialDistrict->findBankById($id);

        $content = new ContentContainer(
            new FluidRow(
                new ComponentCollection(
                    new Legend('Accounts for bank "' . $bank->id . '"'),
                    new Table(
                        new TableRow(
                            new TableHeadSet(
                                new TableHead(TableCellAlignment::VARIANT_REGULAR, 'Number'),
                                new TableHead(TableCellAlignment::VARIANT_REGULAR, 'Overdraft Limit'),
                                new TableHead(TableCellAlignment::VARIANT_REGULAR, 'Balance'),
                            )
                        ),
                        new TableRows(
                            ...array_map(
                                fn (BankAccount $bankAccount): TableRow => new TableRow(
                                    new TableCells(
                                        new TableCell(
                                            TableCellAlignment::VARIANT_REGULAR,
                                            null,
                                            new Link(
                                                $this->getActionUri(
                                                    actionName: 'edit',
                                                    parameters: ['accountNumber' => $bankAccount->number->value]
                                                ),
                                                $bankAccount->number->value
                                            )
                                        ),
                                        new TableCell(
                                            TableCellAlignment::VARIANT_REGULAR,
                                            null,
                                            (string)$bankAccount->getAccountOverdraftLimit()->amount->value
                                        ),
                                        new TableCell(
                                            TableCellAlignment::VARIANT_REGULAR,
                                            null,
                                            (string)$bankAccount->getBalance()->value
                                        ),
                                    )
                                ),
                                $bank->findAllAccounts()
                            )
                        )
                    )
                )
            )
        );

        return (string)$content;
    }

    /**
     * @param array<string,mixed> $parameters
     */
    private function getActionUri(string $actionName, array $parameters = []): Uri
    {
        return new Uri($this->controllerContext
            ->getUriBuilder()
            ->setCreateAbsoluteUri(true)
            ->uriFor(
                actionName: $actionName,
                controllerArguments: $parameters,
                controllerName: 'BankingManagement',
                packageKey: 'Nezaniel.Banking',
                subPackageKey: 'Application'
            ));
    }
}
