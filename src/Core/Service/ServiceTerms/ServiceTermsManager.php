<?php

declare(strict_types=1);

namespace KLS\Core\Service\ServiceTerms;

use KLS\Core\Entity\LegalDocument;
use KLS\Core\Entity\User;
use KLS\Core\Repository\AcceptationLegalDocsRepository;
use KLS\Core\Repository\LegalDocumentRepository;

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
