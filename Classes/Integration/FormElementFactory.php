<?php

/*
 * This script belongs to the Neos Flow package "Nezaniel.Banking".
 */

declare(strict_types=1);

namespace Nezaniel\Banking\Integration;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context as SecurityContext;
use Nezaniel\ComponentView\BackendModuleComponents\Presentation\Block\Form\HiddenField\HiddenField;

#[Flow\Scope('singleton')]
final readonly class FormElementFactory
{
    public function __construct(
        private SecurityContext $securityContext,
    ) {
    }

    public function createCsrfProtectionHiddenField(): HiddenField
    {
        return new HiddenField(
            '__csrfToken',
            $this->securityContext->getCsrfProtectionToken()
        );
    }
}
