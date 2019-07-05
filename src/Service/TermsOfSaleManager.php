<?php

declare(strict_types=1);

namespace Unilend\Service;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\core\Loader;
use Unilend\Entity\{AcceptationsLegalDocs, Clients, Companies, Elements, ProjectCgv, Projects, Settings, TreeElements, UniversignEntityInterface};

class TermsOfSaleManager
{
    public const SESSION_KEY_TOS_ACCEPTED = 'user_legal_doc_accepted';

    public const EXCEPTION_CODE_INVALID_EMAIL        = 1;
    public const EXCEPTION_CODE_INVALID_PHONE_NUMBER = 2;
    public const EXCEPTION_CODE_PDF_FILE_NOT_FOUND   = 3;

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var UnilendMailerManager */
    private $mailerManager;
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
     * @param UnilendMailerManager   $mailerManager
     * @param TokenStorageInterface  $tokenStorage
     * @param RequestStack           $requestStack
     * @param string                 $rootDirectory
     * @param string                 $defaultLocale
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        UnilendMailerManager $mailerManager,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        string $rootDirectory,
        string $defaultLocale,
        MessageBusInterface $messageBus
    ) {
        $this->entityManager = $entityManager;
        $this->mailerManager = $mailerManager;
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
     */
    public function acceptCurrentVersion(Clients $client): void
    {
        $termsOfUse = new AcceptationsLegalDocs();
        $termsOfUse
            ->setIdLegalDoc($this->getCurrentVersionId())
            ->setClient($client)
        ;

        $this->entityManager->persist($termsOfUse);
        $this->entityManager->flush();

        $session = $this->requestStack->getCurrentRequest()->getSession();
        $session->remove(self::SESSION_KEY_TOS_ACCEPTED);
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
     * @param Projects       $project
     * @param Companies|null $companySubmitter
     *
     * @throws Exception
     */
    public function sendBorrowerEmail(Projects $project, Companies $companySubmitter = null): void
    {
        /** @var \ficelle $stringManager */
        $stringManager = Loader::loadLib('ficelle');
        $client        = $project->getIdCompany()->getIdClientOwner();

        if (empty($client->getEmail())) {
            throw new Exception('Invalid client email', self::EXCEPTION_CODE_INVALID_EMAIL);
        }

        if (empty($client->getPhone()) || false === $stringManager->isMobilePhoneNumber($client->getPhone())) {
            throw new Exception('Invalid client mobile phone number', self::EXCEPTION_CODE_INVALID_PHONE_NUMBER);
        }

        $termsOfSale = $this->entityManager->getRepository(ProjectCgv::class)->findOneBy(['idProject' => $project]);

        if (null === $termsOfSale) {
            $tree = $this->entityManager->getRepository(Settings::class)->findOneBy(['type' => Settings::TYPE_BORROWER_TOS]);

            if (null === $tree) {
                throw new Exception('Unable to find tree element', self::EXCEPTION_CODE_PDF_FILE_NOT_FOUND);
            }

            $termsOfSale = new ProjectCgv();
            $termsOfSale
                ->setIdProject($project)
                ->setIdTree($tree->getValue())
                ->setName($termsOfSale->generateFileName())
                ->setIdUniversign('')
                ->setUrlUniversign('')
                ->setStatus(UniversignEntityInterface::STATUS_PENDING)
            ;

            $this->entityManager->persist($termsOfSale);
            $this->entityManager->flush($termsOfSale);
        }

        $pdfElement = $this->entityManager->getRepository(TreeElements::class)->findOneBy([
            'idTree'    => $termsOfSale->getIdTree(),
            'idElement' => Elements::TYPE_PDF_TERMS_OF_SALE,
            'idLangue'  => mb_substr($this->locale, 0, 2),
        ]);

        if (null === $pdfElement || empty($pdfElement->getValue())) {
            throw new Exception('Unable to find PDF', self::EXCEPTION_CODE_PDF_FILE_NOT_FOUND);
        }

        $pdfPath = $this->rootDirectory . '/../public/default/var/fichiers/' . $pdfElement->getValue();

        if (false === file_exists($pdfPath)) {
            throw new Exception('PDF file does not exist', self::EXCEPTION_CODE_PDF_FILE_NOT_FOUND);
        }

        if (false === is_dir($this->rootDirectory . '/../' . ProjectCgv::BASE_PATH)) {
            mkdir($this->rootDirectory . '/../' . ProjectCgv::BASE_PATH);
        }

        if (false === file_exists($this->rootDirectory . '/../' . ProjectCgv::BASE_PATH . $termsOfSale->getName())) {
            copy($pdfPath, $this->rootDirectory . '/../' . ProjectCgv::BASE_PATH . $termsOfSale->getName());
        }

        $this->mailerManager->sendProjectTermsOfSale($termsOfSale, $companySubmitter);
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
            ->findOneBy(['client' => $client, 'idLegalDoc' => $termsOfSaleId])
        ;

        return null !== $legalDocsAcceptance;
    }
}
