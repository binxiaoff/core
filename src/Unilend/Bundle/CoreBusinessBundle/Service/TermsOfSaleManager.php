<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{AcceptationsLegalDocs, Clients, Companies, Elements, ProjectCgv, Projects, Settings, UniversignEntityInterface};
use Unilend\core\Loader;

class TermsOfSaleManager
{
    const SESSION_KEY_TOS_ACCEPTED = 'user_legal_doc_accepted';

    const EXCEPTION_CODE_INVALID_EMAIL        = 1;
    const EXCEPTION_CODE_INVALID_PHONE_NUMBER = 2;
    const EXCEPTION_CODE_PDF_FILE_NOT_FOUND   = 3;

    const ID_TREE_ROOT_SECTION_LENDER_TOS = 43;

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var MailerManager */
    private $mailerManager;
    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var RequestStack */
    private $requestStack;
    /** @var string */
    private $rootDirectory;
    /** @var string */
    private $locale;

    /**
     * @param EntityManagerInterface $entityManager
     * @param MailerManager          $mailerManager
     * @param TokenStorageInterface  $tokenStorage
     * @param RequestStack           $requestStack
     * @param string                 $rootDirectory
     * @param string                 $defaultLocale
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        MailerManager $mailerManager,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        string $rootDirectory,
        string $defaultLocale
    )
    {
        $this->entityManager = $entityManager;
        $this->mailerManager = $mailerManager;
        $this->tokenStorage  = $tokenStorage;
        $this->requestStack  = $requestStack;
        $this->rootDirectory = $rootDirectory;
        $this->locale        = $defaultLocale;
    }

    /**
     * If the lender has accepted the last TOS, the session will not be set, and we check if there is a new TOS all the time
     * Otherwise, the session will be set with accepted = false. We check no longer the now TOS, but we read the value from the session.
     */
    public function checkCurrentVersionAccepted(): void
    {
        return;

        $session = $this->requestStack->getCurrentRequest()->getSession();

        if ($session->has(self::SESSION_KEY_TOS_ACCEPTED)) {
            return; // already checked and not accepted
        }

        $token = $this->tokenStorage->getToken();

        if ($token) {
            $client = $token->getUser();

            if ($client instanceof Clients && $client->isLender() && false === $this->hasAcceptedCurrentVersion($client)) {
                $session->set(self::SESSION_KEY_TOS_ACCEPTED, false);
            }
        }
    }

    /**
     * @param Clients $client
     * @param int     $legalDocId
     *
     * @return bool
     */
    public function isAcceptedVersion(Clients $client, int $legalDocId): bool
    {
        $legalDocsAcceptance = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:AcceptationsLegalDocs')
            ->findOneBy(['idClient' => $client, 'idLegalDoc' => $legalDocId]);

        return null !== $legalDocsAcceptance;
    }

    /**
     * @param Clients $client
     *
     * @return int
     */
    private function getCurrentVersionId(Clients $client): int
    {
        if ($client->isNaturalPerson()) {
            return $this->getCurrentVersionForPerson();
        }

        return $this->getCurrentVersionForLegalEntity();
    }

    /**
     * @return int
     */
    public function getCurrentVersionForPerson(): int
    {
        return (int) $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:Settings')
            ->findOneBy(['type' => Settings::TYPE_LENDER_TOS_NATURAL_PERSON])
            ->getValue();
    }

    /**
     * @return int
     */
    public function  getCurrentVersionForLegalEntity(): int
    {
        return (int) $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:Settings')
             ->findOneBy(['type' => Settings::TYPE_LENDER_TOS_LEGAL_ENTITY])
             ->getValue();
    }

    /**
     * @return \DateTime
     */
    public function getDateOfNewTermsOfSaleWithTwoMandates(): \DateTime
    {
        $setting = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:Settings')
            ->findOneBy(['type' => Settings::TYPE_DATE_LENDER_TOS])
            ->getValue();

        return new \DateTime($setting);
    }

    /**
     * @param Clients $client
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function acceptCurrentVersion(Clients $client): void
    {
        if (false === empty($client)) {
            $termsOfUse = new AcceptationsLegalDocs();
            $termsOfUse
                ->setIdLegalDoc($this->getCurrentVersionId($client))
                ->setIdClient($client);

            $this->entityManager->persist($termsOfUse);
            $this->entityManager->flush($termsOfUse);

            $session = $this->requestStack->getCurrentRequest()->getSession();
            $session->remove(self::SESSION_KEY_TOS_ACCEPTED);
        }
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    public function hasAcceptedCurrentVersion(Clients $client): bool
    {
        return $this->isAcceptedVersion($client, $this->getCurrentVersionId($client));
    }

    /**
     * @param Projects       $project
     * @param Companies|null $companySubmitter
     *
     * @throws \Exception
     */
    public function sendBorrowerEmail(Projects $project, Companies $companySubmitter = null): void
    {
        /** @var \ficelle $stringManager */
        $stringManager = Loader::loadLib('ficelle');
        $client        = $project->getIdCompany()->getIdClientOwner();

        if (empty($client->getEmail())) {
            throw new \Exception('Invalid client email', self::EXCEPTION_CODE_INVALID_EMAIL);
        }

        if (empty($client->getTelephone()) || false === $stringManager->isMobilePhoneNumber($client->getTelephone())) {
            throw new \Exception('Invalid client mobile phone number', self::EXCEPTION_CODE_INVALID_PHONE_NUMBER);
        }

        $termsOfSale = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectCgv')->findOneBy(['idProject' => $project]);

        if (null === $termsOfSale) {
            $tree = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => Settings::TYPE_BORROWER_TOS]);

            if (null === $tree) {
                throw new \Exception('Unable to find tree element', self::EXCEPTION_CODE_PDF_FILE_NOT_FOUND);
            }

            $termsOfSale = new ProjectCgv();
            $termsOfSale
                ->setIdProject($project)
                ->setIdTree($tree->getValue())
                ->setName($termsOfSale->generateFileName())
                ->setIdUniversign('')
                ->setUrlUniversign('')
                ->setStatus(UniversignEntityInterface::STATUS_PENDING);

            $this->entityManager->persist($termsOfSale);
            $this->entityManager->flush($termsOfSale);
        }

        $pdfElement = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TreeElements')->findOneBy([
            'idTree'    => $termsOfSale->getIdTree(),
            'idElement' => Elements::TYPE_PDF_TERMS_OF_SALE,
            'idLangue'  => substr($this->locale, 0, 2)
        ]);

        if (null === $pdfElement || empty($pdfElement->getValue())) {
            throw new \Exception('Unable to find PDF', self::EXCEPTION_CODE_PDF_FILE_NOT_FOUND);
        }

        $pdfPath = $this->rootDirectory . '/../public/default/var/fichiers/' . $pdfElement->getValue();

        if (false === file_exists($pdfPath)) {
            throw new \Exception('PDF file does not exist', self::EXCEPTION_CODE_PDF_FILE_NOT_FOUND);
        }

        if (false === is_dir($this->rootDirectory . '/../' . ProjectCgv::BASE_PATH)) {
            mkdir($this->rootDirectory . '/../' . ProjectCgv::BASE_PATH);
        }

        if (false === file_exists($this->rootDirectory . '/../' . ProjectCgv::BASE_PATH . $termsOfSale->getName())) {
            copy($pdfPath, $this->rootDirectory . '/../' . ProjectCgv::BASE_PATH . $termsOfSale->getName());
        }

        $this->mailerManager->sendProjectTermsOfSale($termsOfSale, $companySubmitter);
    }
}
