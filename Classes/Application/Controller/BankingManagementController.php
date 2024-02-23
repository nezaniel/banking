<?php

/*
 * This file is part of the Nezaniel.Banking package.
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Application\Controller;

use GuzzleHttp\Psr7\Uri;
use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Nezaniel\Banking\Domain\Accounting\BlockBankAccount;
use Nezaniel\Banking\Domain\Accounting\CloseBankAccount;
use Nezaniel\Banking\Domain\Accounting\OpenBankAccount;
use Nezaniel\Banking\Domain\Accounting\SetBankAccountOverdraftLimit;
use Nezaniel\Banking\Domain\BankingEventContract;
use Nezaniel\Banking\Domain\MoneyTransfer\MoneyWasTransferred;
use Nezaniel\Banking\Domain\MoneyTransfer\TransferMoney;
use Nezaniel\Banking\Domain\Accounting\UnblockBankAccount;
use Nezaniel\Banking\Domain\Bank;
use Nezaniel\Banking\Domain\BankAccount;
use Nezaniel\Banking\Domain\BankAccountNumber;
use Nezaniel\Banking\Domain\FinancialDistrict;
use Nezaniel\Banking\Integration\FormElementFactory;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\DangerButton\DangerButton;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\DangerButton\Modal;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Form\Form;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Form\FormMethod;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Form\HiddenField\HiddenField;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Form\Label;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Form\Submit\Submit;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Form\TextField\TextField;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Icon\Icon;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Link\Link;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Link\LinkTarget;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Link\LinkVariant;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\ActionTableCell;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\Table;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\TableCell;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\TableCells;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\TableHead;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\TableHeadSet;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\TableRow;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Table\TableRows;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Layout\ContentContainer\ContentContainer;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Layout\Fieldset\Fieldset;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Layout\FluidRow\FluidRow;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Legend\Legend;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Layout\Footer\Footer;
use Nezaniel\ComponentView\Domain\ComponentCollection;
use Nezaniel\Nortex\Presentation\Block\Form\RequiredStatement;

#[Flow\Scope('singleton')]
class BankingManagementController extends AbstractModuleController
{
    #[Flow\Inject]
    protected FinancialDistrict $financialDistrict;

    #[Flow\Inject]
    protected FormElementFactory $formElementFactory;

    #[Flow\Inject]
    protected SecurityContext $securityContext;

    public function indexAction(): string
    {
        $content = new ContentContainer(
            new FluidRow(
                new ComponentCollection(
                    new Legend('Available banks'),
                    new Table(
                        new TableRow(
                            new TableHeadSet(
                                new TableHead(content: 'ID')
                            )
                        ),
                        new TableRows(
                            ...array_map(
                                fn (Bank $bank): TableRow => new TableRow(
                                    new TableCells(
                                        new TableCell(
                                            content: new Link(
                                                $this->getActionUri(
                                                    actionName: 'showBank',
                                                    parameters: ['bankId' => $bank->id]
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

    public function showBankAction(string $bankId): string
    {
        $bank = $this->financialDistrict->findBankById($bankId);

        $content = new ContentContainer(
            new ComponentCollection(
                new FluidRow(
                    new ComponentCollection(
                        new Legend('Accounts for bank "' . $bank->id . '"'),
                        new Table(
                            new TableRow(
                                new TableHeadSet(
                                    new TableHead(content: 'Number'),
                                    new TableHead(content: 'Holder'),
                                    new TableHead(content: 'Overdraft Limit'),
                                    new TableHead(content: 'Balance'),
                                    new TableHead(content: ''),
                                )
                            ),
                            new TableRows(
                                ...array_map(
                                    fn (BankAccount $bankAccount): TableRow => new TableRow(
                                        new TableCells(
                                            new TableCell(
                                                content: new Link(
                                                    $this->getActionUri(
                                                        actionName: 'edit',
                                                        parameters: ['accountNumber' => $bankAccount->number->value]
                                                    ),
                                                    $bankAccount->number->value
                                                )
                                            ),
                                            new TableCell(
                                                content: $bankAccount->getHolder() ?: 'anonymous'
                                            ),
                                            new TableCell(
                                                content: (string)$bankAccount->getAccountOverdraftLimit()->amount->value
                                            ),
                                            new TableCell(
                                                content: (string)$bankAccount->getBalance()->value
                                            ),
                                            new ActionTableCell(
                                                new ComponentCollection(
                                                    new Link(
                                                        $this->getActionUri(
                                                            'editBankAccount',
                                                            [
                                                                'bankId' => $bankId,
                                                                'accountNumber' => $bankAccount->number->value
                                                            ]
                                                        ),
                                                        new Icon('pencil-alt'),
                                                        LinkVariant::VARIANT_PRIMARY_BUTTON,
                                                        'Edit bank account',
                                                        LinkTarget::TARGET_SELF
                                                    ),
                                                    $bankAccount->isBlocked()
                                                        ? new Link(
                                                            $this->getActionUri(
                                                                'unblockBankAccount',
                                                                [
                                                                    'bankId' => $bankId,
                                                                    'command' => [
                                                                        'accountNumber' => $bankAccount->number->value
                                                                    ]
                                                                ]
                                                            ),
                                                            new Icon('unlock'),
                                                            LinkVariant::VARIANT_PRIMARY_BUTTON,
                                                            'Unlock bank account',
                                                            LinkTarget::TARGET_SELF
                                                        )
                                                        : new Link(
                                                            $this->getActionUri(
                                                                'blockBankAccount',
                                                                [
                                                                    'bankId' => $bankId,
                                                                    'command' => [
                                                                        'accountNumber' => $bankAccount->number->value
                                                                    ]
                                                                ]
                                                            ),
                                                            new Icon('lock'),
                                                            LinkVariant::VARIANT_PRIMARY_BUTTON,
                                                            'Lock bank account',
                                                            LinkTarget::TARGET_SELF
                                                        ),
                                                    new DangerButton(
                                                        'bankaccount-' . $bankAccount->number->value,
                                                        new Icon('trash-alt'),
                                                    ),
                                                    new Modal(
                                                        'bankaccount-' . $bankAccount->number->value,
                                                        'Confirm closing account ' . $bankAccount->number->value,
                                                        'The account will be irrevocably closed',
                                                        $this->getActionUri(
                                                            'closeBankAccount',
                                                            [
                                                                'bankId' => $bankId,
                                                                'command' => [
                                                                    'accountNumber' => $bankAccount->number->value
                                                                ]
                                                            ]
                                                        ),
                                                        $this->securityContext->getCsrfProtectionToken(),
                                                        'Close account',
                                                    )
                                                )
                                            )
                                        )
                                    ),
                                    $bank->findAllAccounts()
                                )
                            )
                        ),
                    )
                ),
                new FluidRow(
                    new ComponentCollection(
                        new Fieldset(
                            new ComponentCollection(
                                new Legend('Open new account'),
                                new Form(
                                    'openBankAccount',
                                    FormMethod::METHOD_POST,
                                    $this->getActionUri('openBankAccount'),
                                    $this->formElementFactory->createCsrfProtectionHiddenField(),
                                    new HiddenField('moduleArguments[bankId]', $bank->id),
                                    new TextField(
                                        'accountNumber',
                                        'moduleArguments[command][accountNumber]',
                                        requiredStatement: new RequiredStatement(),
                                        label: new Label('Account number')
                                    ),
                                    new TextField('holder', 'moduleArguments[command][holder]', label: new Label('Account holder')),
                                    new Submit('Open bank account')
                                )
                            )
                        )
                    )
                ),
                new Footer(
                    new Link(
                        $this->getActionUri('index'),
                        'Cancel',
                        LinkVariant::VARIANT_BUTTON
                    )
                )
            )
        );

        return (string)$content;
    }

    public function editBankAccountAction(string $bankId, string $accountNumber): string
    {
        $bank = $this->financialDistrict->findBankById($bankId);
        $account = $bank->findAccount(new BankAccountNumber($accountNumber));

        $content = new ContentContainer(
            new ComponentCollection(
                new FluidRow(
                    new ComponentCollection(
                        new Fieldset(
                            new ComponentCollection(
                                new Legend('Transaction history'),
                                new Table(
                                    new TableRow(
                                        new TableHeadSet(
                                            new TableHead(content: 'Date (UTC)'),
                                            new TableHead(content: 'From'),
                                            new TableHead(content: 'To'),
                                            new TableHead(content: 'Amount (' . $bank->currency->value . ')'),
                                        )
                                    ),
                                    new TableRows(
                                        ...array_map(
                                            fn (MoneyWasTransferred $event): TableRow => new TableRow(
                                                new TableCells(
                                                    new TableCell(
                                                        content: $event->startTime->format('Y-m-d H:i:s'),
                                                    ),
                                                    new TableCell(
                                                        content: $event->from->equals($account->number)
                                                            ? ''
                                                            : $event->from->value
                                                    ),
                                                    new TableCell(
                                                        content: $event->to->equals($account->number)
                                                            ? ''
                                                            : $event->from->value
                                                    ),
                                                    new TableCell(
                                                        content: (string)$event->amount->value
                                                    )
                                                )
                                            ),
                                            array_filter(
                                                iterator_to_array($account->getEvents()),
                                                fn (BankingEventContract $event): bool => $event instanceof MoneyWasTransferred
                                            )
                                        )
                                    )
                                )
                            )
                        ),
                        new Fieldset(
                            new ComponentCollection(
                                new Legend('Set overdraft limit'),
                                new Form(
                                    'setOverdraftLimit',
                                    FormMethod::METHOD_POST,
                                    $this->getActionUri('setBankAccountOverdraftLimit'),
                                    $this->formElementFactory->createCsrfProtectionHiddenField(),
                                    new HiddenField('moduleArguments[bankId]', $bank->id),
                                    new HiddenField('moduleArguments[command][accountNumber]', $account->number->value),
                                    new HiddenField('moduleArguments[command][accountOverdraftLimit][amount][currency]', $bank->currency->value),
                                    new TextField(
                                        'accountNumber',
                                        'moduleArguments[command][accountOverdraftLimit][amount][value]',
                                        requiredStatement: new RequiredStatement(),
                                        label: new Label('Overdraft limit (' . $bank->currency->value . ')'),
                                        value: (string)$account->getAccountOverdraftLimit()->amount->value
                                    ),
                                    new Submit('Set overdraft limit')
                                ),
                                new Legend('Transfer money from bank'),
                                new Form(
                                    'transferMoney',
                                    FormMethod::METHOD_POST,
                                    $this->getActionUri('transferMoney'),
                                    $this->formElementFactory->createCsrfProtectionHiddenField(),
                                    new HiddenField('moduleArguments[bankId]', $bank->id),
                                    new HiddenField('moduleArguments[accountNumber]', $bank->id),
                                    new HiddenField('moduleArguments[command][to]', $account->number->value),
                                    new HiddenField('moduleArguments[command][amount][currency]', $bank->currency->value),
                                    new TextField(
                                        'accountNumber',
                                        'moduleArguments[command][amount][value]',
                                        requiredStatement: new RequiredStatement(),
                                        label: new Label('Amount (' . $bank->currency->value . ')'),
                                    ),
                                    new Submit('Transfer money')
                                ),
                            ),
                            1
                        )
                    )
                ),
                new Footer(
                    new Link(
                        $this->getActionUri('showBank', ['bankId' => $bankId]),
                        'Cancel',
                        LinkVariant::VARIANT_BUTTON
                    )
                )
            )
        );

        return (string)$content;
    }

    /**
     * @param array<string,mixed> $command
     */
    public function openBankAccountAction(string $bankId, array $command): never
    {
        $bank = $this->requireBank($bankId);

        try {
            $bank->openAccount(OpenBankAccount::fromArray($command));
            $this->addFlashMessage('Account "' . $command['accountNumber'] . '" opened successfully');
            $this->redirect(actionName: 'showBank', arguments: ['bankId' => $bankId]);
        } catch (\DomainException $exception) {
            $this->handleException($exception);
        }
    }

    public function setBankAccountOverdraftLimitAction(string $bankId, array $command): void
    {
        $bank = $this->requireBank($bankId);

        try {
            $bank->setAccountOverdraftLimit(SetBankAccountOverdraftLimit::fromArray($command));
            $this->addFlashMessage('Overdraft limit set for account ' . $command['accountNumber']);
            $this->redirect(actionName: 'editBankAccount', arguments: ['bankId' => $bankId, 'accountNumber' => $command['accountNumber']]);
        } catch (\DomainException $exception) {
            $this->handleException($exception);
        }
    }

    public function blockBankAccountAction(string $bankId, array $command): void
    {
        $bank = $this->requireBank($bankId);

        try {
            $bank->blockAccount(BlockBankAccount::fromArray($command));
            $this->addFlashMessage('Account "' . $command['accountNumber'] . '" blocked successfully');
            $this->redirect(actionName: 'showBank', arguments: ['bankId' => $bankId]);
        } catch (\DomainException $exception) {
            $this->handleException($exception);
        }
    }

    public function unblockBankAccountAction(string $bankId, array $command): void
    {
        $bank = $this->requireBank($bankId);

        try {
            $bank->unblockAccount(UnblockBankAccount::fromArray($command));
            $this->addFlashMessage('Account "' . $command['accountNumber'] . '" unblocked successfully');
            $this->redirect(actionName: 'showBank', arguments: ['bankId' => $bankId]);
        } catch (\DomainException $exception) {
            $this->handleException($exception);
        }
    }

    public function closeBankAccountAction(string $bankId, array $command): void
    {
        $bank = $this->requireBank($bankId);

        try {
            $bank->closeAccount(CloseBankAccount::fromArray($command));
            $this->addFlashMessage('Account "' . $command['accountNumber'] . '" closed successfully');
            $this->redirect(actionName: 'showBank', arguments: ['bankId' => $bankId]);
        } catch (\DomainException $exception) {
            $this->handleException($exception);
        }
    }

    public function transferMoneyAction(string $bankId, string $accountNumber, array $command): void
    {
        $bank = $this->requireBank($bankId);
        $account = $bank->findAccount(new BankAccountNumber($accountNumber));

        try {
            $account->transferMoney(TransferMoney::fromArray($command));
            $this->addFlashMessage('Money transferred successfully');
            $this->redirect(actionName: 'editBankAccount', arguments: ['bankId' => $bankId, 'accountNumber' => $command['to']]);
        } catch (\DomainException $exception) {
            $this->handleException($exception);
        }
    }

    private function requireBank(string $bankId): Bank
    {
        $bank = $this->financialDistrict->findBankById($bankId);
        if (!$bank) {
            $this->addFlashMessage('Unknown bank ' . $bankId, severity: Message::SEVERITY_WARNING);
            $this->redirect('index');
        }

        return $bank;
    }

    private function handleException(\DomainException $exception): never
    {
        $this->addFlashMessage($exception->getMessage(), severity: Message::SEVERITY_ERROR);
        $this->redirect('index');
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
