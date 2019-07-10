<?php

declare(strict_types=1);

namespace Unilend\Service\ServiceTerms;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Entity\{AcceptationsLegalDocs, Clients, Settings, Tree};
use Unilend\Message\ServiceTerms\ServiceTermsAccepted;

class ServiceTermsManager
{
    public const SESSION_KEY_SERVICE_TERMS_ACCEPTED = 'user_legal_doc_accepted';

    public const EXCEPTION_CODE_INVALID_EMAIL        = 1;
    public const EXCEPTION_CODE_INVALID_PHONE_NUMBER = 2;
    public const EXCEPTION_CODE_PDF_FILE_NOT_FOUND   = 3;

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var RequestStack */
    private $requestStack;
    /** @var MessageBusInterface */
    private $messageBus;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TokenStorageInterface  $tokenStorage
     * @param RequestStack           $requestStack
     * @param MessageBusInterface    $messageBus
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        MessageBusInterface $messageBus
    ) {
        $this->entityManager = $entityManager;
        $this->tokenStorage  = $tokenStorage;
        $this->requestStack  = $requestStack;
        $this->messageBus    = $messageBus;
    }

    /**
     * If the lender has accepted the last service terms, the session will not be set, and we check if there is a new service terms at all time
     * Otherwise, the session will be set to accepted = false. With accepted = false, we check no longer the new service terms, but we read the value from the session.
     */
    public function checkCurrentVersionAccepted(): void
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        if ($session->has(self::SESSION_KEY_SERVICE_TERMS_ACCEPTED)) {
            return; // already checked and not accepted
        }

        $token = $this->tokenStorage->getToken();

        if ($token) {
            $client = $token->getUser();

            if ($client instanceof Clients && false === $this->hasAcceptedCurrentVersion($client)) {
                $session->set(self::SESSION_KEY_SERVICE_TERMS_ACCEPTED, false);
            }
        }
    }

    /**
     * @return int
     */
    public function getCurrentVersionId(): int
    {
        return (int) $this->entityManager
            ->getRepository(Settings::class)
            ->findOneBy(['type' => Settings::TYPE_SERVICE_TERMS_PAGE_ID])
            ->getValue()
        ;
    }

    /**
     * @param Clients $client
     *
     * @return AcceptationsLegalDocs
     */
    public function acceptCurrentVersion(Clients $client): AcceptationsLegalDocs
    {
        $legalDocument           = $this->entityManager->getRepository(Tree::class)->find($this->getCurrentVersionId());
        $ServiceTermsAcceptation = new AcceptationsLegalDocs();
        $ServiceTermsAcceptation
            ->setLegalDoc($legalDocument)
            ->setClient($client)
        ;

        $this->entityManager->persist($ServiceTermsAcceptation);
        $this->entityManager->flush();

        $session = $this->requestStack->getCurrentRequest()->getSession();
        $session->remove(self::SESSION_KEY_SERVICE_TERMS_ACCEPTED);

        $this->messageBus->dispatch(new ServiceTermsAccepted($ServiceTermsAcceptation->getIdAcceptation()));

        return $ServiceTermsAcceptation;
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    public function hasAcceptedCurrentVersion(Clients $client): bool
    {
        return $this->hasAccepted($client, $this->getCurrentVersionId());
    }

    /**
     * @param Clients $client
     * @param int     $serviceTermsId
     *
     * @return bool
     */
    private function hasAccepted(Clients $client, int $serviceTermsId): bool
    {
        $legalDocsAcceptance = $this->entityManager
            ->getRepository(AcceptationsLegalDocs::class)
            ->findOneBy(['client' => $client, 'legalDoc' => $serviceTermsId])
        ;

        return null !== $legalDocsAcceptance;
    }
}
