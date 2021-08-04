<?php

declare(strict_types=1);

namespace Unilend\Core\Service\ServiceTerms;

use Unilend\Core\Entity\LegalDocument;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\AcceptationLegalDocsRepository;
use Unilend\Core\Repository\LegalDocumentRepository;

class ServiceTermsManager
{
    private AcceptationLegalDocsRepository $acceptationLegalDocsRepository;

    private LegalDocumentRepository $legalDocumentRepository;

    public function __construct(AcceptationLegalDocsRepository $acceptationLegalDocsRepository, LegalDocumentRepository $legalDocumentRepository)
    {
        $this->acceptationLegalDocsRepository = $acceptationLegalDocsRepository;
        $this->legalDocumentRepository        = $legalDocumentRepository;
    }

    public function getCurrentVersion(): LegalDocument
    {
        // TODO Should we find the latest by type CALS-4049
        return $this->legalDocumentRepository->find(LegalDocument::CURRENT_SERVICE_TERMS_ID);
    }

    public function hasAccepted(User $user, LegalDocument $serviceTerms): bool
    {
        $legalDocsAcceptance = $this->acceptationLegalDocsRepository->findOneBy(['acceptedBy' => $user, 'legalDoc' => $serviceTerms]);

        return null !== $legalDocsAcceptance;
    }
}
