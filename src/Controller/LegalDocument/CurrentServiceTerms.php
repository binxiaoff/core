<?php

declare(strict_types=1);

namespace Unilend\Controller\LegalDocument;

use Unilend\Entity\LegalDocument;
use Unilend\Service\ServiceTerms\ServiceTermsManager;

class CurrentServiceTerms
{
    /** @var ServiceTermsManager */
    private ServiceTermsManager $serviceTermsManager;

    /**
     * @param ServiceTermsManager $serviceTermsManager
     */
    public function __construct(ServiceTermsManager $serviceTermsManager)
    {
        $this->serviceTermsManager = $serviceTermsManager;
    }

    /**
     * @return LegalDocument
     */
    public function __invoke(): LegalDocument
    {
        return $this->serviceTermsManager->getCurrentVersion();
    }
}
