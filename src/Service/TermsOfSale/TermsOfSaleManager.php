<?php

declare(strict_types=1);

namespace Unilend\Service\TermsOfSale;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Entity\{AcceptationsLegalDocs, Clients, Settings, Tree};
use Unilend\Message\TermsOfSale\TermsOfSaleAccepted;

class TermsOfSaleManager
{
    public const SESSION_KEY_TOS_ACCEPTED = 'user_legal_doc_accepted';

    public const EXCEPTION_CODE_INVALID_EMAIL        = 1;
    public const EXCEPTION_CODE_INVALID_PHONE_NUMBER = 2;
    public const EXCEPTION_CODE_PDF_FILE_NOT_FOUND   = 3;

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var RequestStack */
    private $requestStack;
    /** @var string */
    private $rootDirectory;
    /** @var string */
    private $locale;
    /** @var MessageBusInterface */
    private $messageBus;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TokenStorageInterface  $tokenStorage
     * @param RequestStack           $requestStack
     * @param string                 $rootDirectory
     * @param string                 $defaultLocale
     * @param MessageBusInterface    $messageBus
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        string $rootDirectory,
        string $defaultLocale,
        MessageBusInterface $messageBus
    ) {
        $this->entityManager = $entityManager;
        $this->tokenStorage  = $tokenStorage;
        $this->requestStack  = $requestStack;
        $this->rootDirectory = $rootDirectory;
        $this->locale        = $defaultLocale;
        $this->messageBus    = $messageBus;
    }

    /**
     * If the lender has accepted the last TOS, the session will not be set, and we check if there is a new TOS at all time
     * Otherwise, the session will be set to accepted = false. With accepted = false, we check no longer the new TOS, but we read the value from the session.
     */
    public function checkCurrentVersionAccepted(): void
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        if ($session->has(self::SESSION_KEY_TOS_ACCEPTED)) {
            return; // already checked and not accepted
        }

        $token = $this->tokenStorage->getToken();

        if ($token) {
            $client = $token->getUser();

            if ($client instanceof Clients && false === $this->hasAcceptedCurrentVersion($client)) {
                $session->set(self::SESSION_KEY_TOS_ACCEPTED, false);
            }
        }
    }

    /**
     * @deprecated
     *
     * @return int
     */
    public function getCurrentVersionForPerson(): int
    {
        return (int) $this->entityManager
            ->getRepository(Settings::class)
            ->findOneBy(['type' => Settings::TYPE_LENDER_TOS_NATURAL_PERSON])
            ->getValue()
        ;
    }

    /**
     * @return int
     */
    public function getCurrentVersionId(): int
    {
        return (int) $this->entityManager
            ->getRepository(Settings::class)
            ->findOneBy(['type' => Settings::TYPE_TERMS_OF_SALE_PAGE_ID])
            ->getValue()
        ;
    }

    /**
     * @todo to delete
     *
     * @deprecated no need any more
     *
     * @throws Exception
     *
     * @return DateTime
     */
    public function getDateOfNewTermsOfSaleWithTwoMandates(): DateTime
    {
        $setting = $this->entityManager
            ->getRepository(Settings::class)
            ->findOneBy(['type' => Settings::TYPE_DATE_LENDER_TOS])
            ->getValue()
        ;

        return new DateTime($setting);
    }

    /**
     * @param Clients $client
     *
     * @return AcceptationsLegalDocs
     */
    public function acceptCurrentVersion(Clients $client): AcceptationsLegalDocs
    {
        $legalDocument          = $this->entityManager->getRepository(Tree::class)->find($this->getCurrentVersionId());
        $TermsOfSaleAcceptation = new AcceptationsLegalDocs();
        $TermsOfSaleAcceptation
            ->setLegalDoc($legalDocument)
            ->setClient($client)
        ;

        $this->entityManager->persist($TermsOfSaleAcceptation);
        $this->entityManager->flush();

        $session = $this->requestStack->getCurrentRequest()->getSession();
        $session->remove(self::SESSION_KEY_TOS_ACCEPTED);

        $this->messageBus->dispatch(new TermsOfSaleAccepted($TermsOfSaleAcceptation->getIdAcceptation()));

        return $TermsOfSaleAcceptation;
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
     * @param int     $termsOfSaleId
     *
     * @return bool
     */
    private function hasAccepted(Clients $client, int $termsOfSaleId): bool
    {
        $legalDocsAcceptance = $this->entityManager
            ->getRepository(AcceptationsLegalDocs::class)
            ->findOneBy(['client' => $client, 'legalDoc' => $termsOfSaleId])
        ;

        return null !== $legalDocsAcceptance;
    }
}
