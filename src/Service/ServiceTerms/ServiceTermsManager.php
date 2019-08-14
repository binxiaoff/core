<?php

declare(strict_types=1);

namespace Unilend\Service\ServiceTerms;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Entity\{AcceptationsLegalDocs, Clients, LegalDocument, Settings};
use Unilend\Message\ServiceTerms\ServiceTermsAccepted;
use Unilend\Repository\{AcceptationLegalDocsRepository, LegalDocumentRepository, SettingsRepository};

class ServiceTermsManager
{
    public const SESSION_KEY_SERVICE_TERMS_ACCEPTED = 'user_legal_doc_accepted';

    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var SessionInterface */
    private $session;
    /** @var MessageBusInterface */
    private $messageBus;
    /** @var AcceptationLegalDocsRepository */
    private $acceptationLegalDocsRepository;
    /** @var LegalDocumentRepository */
    private $legalDocumentRepository;
    /** @var SettingsRepository */
    private $settingsRepository;

    /**
     * @param AcceptationLegalDocsRepository $acceptationLegalDocsRepository
     * @param LegalDocumentRepository        $legalDocumentRepository
     * @param SettingsRepository             $settingsRepository
     * @param TokenStorageInterface          $tokenStorage
     * @param SessionInterface               $session
     * @param MessageBusInterface            $messageBus
     */
    public function __construct(
        AcceptationLegalDocsRepository $acceptationLegalDocsRepository,
        LegalDocumentRepository $legalDocumentRepository,
        SettingsRepository $settingsRepository,
        TokenStorageInterface $tokenStorage,
        SessionInterface $session,
        MessageBusInterface $messageBus
    ) {
        $this->acceptationLegalDocsRepository = $acceptationLegalDocsRepository;
        $this->legalDocumentRepository        = $legalDocumentRepository;
        $this->settingsRepository             = $settingsRepository;
        $this->tokenStorage                   = $tokenStorage;
        $this->session                        = $session;
        $this->messageBus                     = $messageBus;
    }

    /**
     * If the lender has accepted the last service terms, the session will not be set, and we check if there is a new service terms at all time
     * Otherwise, the session will be set to accepted = false. With accepted = false, we check no longer the new service terms, but we read the value from the session.
     */
    public function checkCurrentVersionAccepted(): void
    {
        if ($this->session->has(self::SESSION_KEY_SERVICE_TERMS_ACCEPTED)) {
            return; // already checked and not accepted
        }

        $token = $this->tokenStorage->getToken();

        if ($token) {
            $client = $token->getUser();

            if ($client instanceof Clients && false === $this->hasAccepted($client, $this->getCurrentVersion())) {
                $this->session->set(self::SESSION_KEY_SERVICE_TERMS_ACCEPTED, false);
            }
        }
    }

    /**
     * @return LegalDocument
     */
    public function getCurrentVersion(): LegalDocument
    {
        $currentVersionId = $this->settingsRepository->findOneBy(['type' => Settings::TYPE_SERVICE_TERMS_PAGE_ID])->getValue();

        return $this->legalDocumentRepository->find($currentVersionId);
    }

    /**
     * @param Clients $client
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return AcceptationsLegalDocs
     */
    public function acceptCurrentVersion(Clients $client): AcceptationsLegalDocs
    {
        $serviceTermsAcceptation = new AcceptationsLegalDocs();
        $serviceTermsAcceptation
            ->setLegalDoc($this->getCurrentVersion())
            ->setClient($client)
        ;

        $this->acceptationLegalDocsRepository->save($serviceTermsAcceptation);

        $this->session->remove(self::SESSION_KEY_SERVICE_TERMS_ACCEPTED);

        $this->messageBus->dispatch(new ServiceTermsAccepted($serviceTermsAcceptation->getIdAcceptation()));

        return $serviceTermsAcceptation;
    }

    /**
     * @param Clients       $client
     * @param LegalDocument $serviceTerms
     *
     * @return bool
     */
    private function hasAccepted(Clients $client, LegalDocument $serviceTerms): bool
    {
        $legalDocsAcceptance = $this->acceptationLegalDocsRepository->findOneBy(['client' => $client, 'legalDoc' => $serviceTerms]);

        return null !== $legalDocsAcceptance;
    }
}
