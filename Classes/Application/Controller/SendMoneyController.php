<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Application\Controller;

use Neos\Flow\Annotations as Flow;
use Nezaniel\Banking\Application\CannotSendMoneyToSelf;
use Nezaniel\Banking\Application\MoneyTransferWasBlocked;
use Nezaniel\Banking\Application\MoneyWasSent;
use Nezaniel\Banking\Application\RecipientDoesNotExist;
use Nezaniel\Banking\Application\SendMoney;
use Nezaniel\Banking\Domain\Bank;
use Nezaniel\Banking\Domain\FinancialDistrict;
use Nezaniel\Banking\Domain\MoneyTransfer\TransferMoney;
use Sitegeist\SchemeOnYou\Application\OpenApiController;
use Sitegeist\SchemeOnYou\Domain\Metadata\RequestBody;
use Sitegeist\SchemeOnYou\Domain\Path\RequestBodyContentType;

final class SendMoneyController extends OpenApiController
{
    public function __construct(
        private readonly FinancialDistrict $financialDistrict
    ) {
    }

    #[Flow\Route(uriPattern: 'banking/sendmoney', httpMethods: ['POST'])]
    public function postAction(
        #[RequestBody(RequestBodyContentType::CONTENT_TYPE_FORM)]
        SendMoney $command
    ): MoneyWasSent|CannotSendMoneyToSelf|RecipientDoesNotExist|MoneyTransferWasBlocked {
        $bank = $this->financialDistrict->findBankById('broken');
        assert($bank instanceof Bank);
        $bankAccount = $bank->findAccount($command->from);
        try {
            $bankAccount->transferMoney(new TransferMoney(
                $command->to,
                $command->amount
            ));
            return new MoneyWasSent();
        } catch (\DomainException $exception) {
            switch ($exception->getCode()) {
                case 1708703031:
                    $this->response->setStatusCode(400);
                    return new CannotSendMoneyToSelf();
                case 1707259253:
                    $this->response->setStatusCode(404);
                    return new RecipientDoesNotExist();
                case 1708674826:
                case 1707258982:
                    $this->response->setStatusCode(403);
                    return new MoneyTransferWasBlocked($exception->getMessage());
                default:
                    throw $exception;
            }
        }
    }
}
