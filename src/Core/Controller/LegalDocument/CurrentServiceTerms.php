<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\LegalDocument;

use Unilend\Core\Entity\LegalDocument;
use Unilend\Core\Service\ServiceTerms\ServiceTermsManager;

class CurrentServiceTerms
{
    private ServiceTermsManager $serviceTermsManager;

    public function __construct(ServiceTermsManager $serviceTermsManager)
    {
        $this->serviceTermsManager = $serviceTermsManager;
    }

    public function __invoke(): LegalDocument
    {
        return $this->serviceTermsManager->getCurrentVersion();
    }
}
