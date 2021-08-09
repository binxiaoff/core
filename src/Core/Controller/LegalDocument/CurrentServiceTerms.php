<?php

declare(strict_types=1);

namespace KLS\Core\Controller\LegalDocument;

use KLS\Core\Entity\LegalDocument;
use KLS\Core\Service\ServiceTerms\ServiceTermsManager;

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
