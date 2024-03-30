<?php

/*
 * This file is part of the Nezaniel.Banking package.
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Nezaniel\Banking\Domain\FinancialDistrict;

class BankingCommandController extends CommandController
{
    #[Flow\Inject]
    protected FinancialDistrict $financialDistrict;

    public function setupAllCommand(): void
    {
        $banks = $this->financialDistrict->findAllBanks();
        $this->outputLine('Setting up banks...');
        $this->output->progressStart(count($banks));
        foreach ($banks as $bank) {
            $bank->setUp();
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        $this->outputLine('');
    }
}
