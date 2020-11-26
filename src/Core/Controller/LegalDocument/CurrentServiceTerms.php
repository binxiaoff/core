<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\LegalDocument;

use Unilend\Core\Entity\LegalDocument;
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
