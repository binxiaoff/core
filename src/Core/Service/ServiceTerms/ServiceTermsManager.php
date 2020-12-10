<?php

declare(strict_types=1);

namespace Unilend\Core\Service\ServiceTerms;

use Unilend\Core\Entity\Clients;
use Unilend\Core\Entity\{LegalDocument};
use Unilend\Core\Repository\{AcceptationLegalDocsRepository, LegalDocumentRepository};

class ServiceTermsManager
{
    /** @var AcceptationLegalDocsRepository */
    private AcceptationLegalDocsRepository $acceptationLegalDocsRepository;

    /** @var LegalDocumentRepository */
    private LegalDocumentRepository $legalDocumentRepository;

    /**
     * @param AcceptationLegalDocsRepository $acceptationLegalDocsRepository
     * @param LegalDocumentRepository        $legalDocumentRepository
     */
    public function __construct(AcceptationLegalDocsRepository $acceptationLegalDocsRepository, LegalDocumentRepository $legalDocumentRepository)
    {
        $this->acceptationLegalDocsRepository = $acceptationLegalDocsRepository;
        $this->legalDocumentRepository        = $legalDocumentRepository;
    }

    /**
     * @return LegalDocument
     */
    public function getCurrentVersion(): LegalDocument
    {
        return $this->legalDocumentRepository->find(LegalDocument::CURRENT_SERVICE_TERMS);
    }

    /**
     * @param Clients       $client
     * @param LegalDocument $serviceTerms
     *
     * @return bool
     */
    public function hasAccepted(Clients $client, LegalDocument $serviceTerms): bool
    {
        $legalDocsAcceptance = $this->acceptationLegalDocsRepository->findOneBy(['acceptedBy' => $client, 'legalDoc' => $serviceTerms]);

        return null !== $legalDocsAcceptance;
    }
}
