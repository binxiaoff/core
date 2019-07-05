<?php

namespace Unilend\Service\Document;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Knp\Snappy\Pdf;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Unilend\Entity\{AcceptationsLegalDocs, AddressType, ClientAddress, Companies, CompanyAddress, Elements, Loans, TreeElements, Wallet, WalletType};
use Unilend\Service\TermsOfSaleManager;

class LenderTermsOfSaleGenerator implements DocumentGeneratorInterface
{
    public const PATH = 'pdf' . DIRECTORY_SEPARATOR . 'cgv_preteurs';

    public const LEGAL_ENTITY_PLACEHOLDERS = [
        '[Civilite]',
        '[Prenom]',
        '[Nom]',
        '[Fonction]',
        '[Raison_sociale]',
        '[SIREN]',
        '[adresse_fiscale]',
        '[date_validation_cgv]',
    ];

    public const NATURAL_PERSON_PLACEHOLDERS = [
        '[Civilite]',
        '[Prenom]',
        '[Nom]',
        '[date]',
        '[ville_naissance]',
        '[adresse_fiscale]',
        '[date_validation_cgv]',
    ];

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var TermsOfSaleManager */
    private $termsOfSaleManager;
    /** @var Filesystem */
    private $filesystem;
    /** @var string */
    private $protectedPath;
    /** @var Environment */
    private $twig;
    /** @var Pdf */
    private $snappy;
    /** @var string */
    private $staticUrl;
    /** @var string */
    private $staticPath;
    /** @var \NumberFormatter */
    private $numberFormatter;
    /** @var \NumberFormatter */
    private $currencyFormatter;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TermsOfSaleManager     $termsOfSaleManager
     * @param Filesystem             $filesystem
     * @param string                 $protectedPath
     * @param string                 $staticPath
     * @param Environment            $twig
     * @param Pdf                    $snappy
     * @param Packages               $assetsPackages
     * @param \NumberFormatter       $numberFormatter
     * @param \NumberFormatter       $currencyFormatter
     * @param LoggerInterface        $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TermsOfSaleManager $termsOfSaleManager,
        Filesystem $filesystem,
        string $protectedPath,
        string $staticPath,
        Environment $twig,
        Pdf $snappy,
        Packages $assetsPackages,
        \NumberFormatter $numberFormatter,
        \NumberFormatter $currencyFormatter,
        LoggerInterface $logger
    ) {
        $this->entityManager      = $entityManager;
        $this->termsOfSaleManager = $termsOfSaleManager;
        $this->filesystem         = $filesystem;
        $this->protectedPath      = $protectedPath;
        $this->staticPath         = $staticPath;
        $this->twig               = $twig;
        $this->snappy             = $snappy;
        $this->staticUrl          = $assetsPackages->getUrl('');
        $this->numberFormatter    = $numberFormatter;
        $this->currencyFormatter  = $currencyFormatter;
        $this->logger             = $logger;

        $this->snappy->setBinary('/usr/local/bin/wkhtmltopdf');
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return self::CONTENT_TYPE_PDF;
    }

    /**
     * @param AcceptationsLegalDocs $acceptedLegalDoc
     *
     * @throws Exception
     *
     * @return string
     */
    public function getPath($acceptedLegalDoc): string
    {
        if (false === $acceptedLegalDoc instanceof AcceptationsLegalDocs) {
            $parameterType = gettype($acceptedLegalDoc);
            $parameterType = 'object' === $parameterType ? get_class($acceptedLegalDoc) : $parameterType;

            throw new \InvalidArgumentException('AcceptationsLegalDocs entity expected, got "' . $parameterType . '"');
        }

        return $this->protectedPath . self::PATH . DIRECTORY_SEPARATOR . $acceptedLegalDoc->getClient()->getIdClient() . DIRECTORY_SEPARATOR . $this->getName($acceptedLegalDoc);
    }

    /**
     * @param AcceptationsLegalDocs $acceptedLegalDoc
     *
     * @return string
     */
    public function getName(AcceptationsLegalDocs $acceptedLegalDoc)
    {
        return 'cgv_preteurs-' . $acceptedLegalDoc->getClient()->getHash() . '-' . $acceptedLegalDoc->getIdLegalDoc() . '.pdf';
    }

    /**
     * @param AcceptationsLegalDocs $acceptedLegalDoc
     *
     * @throws Exception
     *
     * @return bool
     */
    public function exists($acceptedLegalDoc): bool
    {
        $path = $this->getPath($acceptedLegalDoc);

        return $this->filesystem->exists($path);
    }

    /**
     * @param AcceptationsLegalDocs $acceptedLegalDoc
     *
     * @throws Exception
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function generate($acceptedLegalDoc): void
    {
        if (false === $acceptedLegalDoc instanceof AcceptationsLegalDocs) {
            $parameterType = gettype($acceptedLegalDoc);
            $parameterType = 'object' === $parameterType ? get_class($acceptedLegalDoc) : $parameterType;

            throw new \InvalidArgumentException('AcceptationsLegalDocs entity expected, got "' . $parameterType . '"');
        }

        $template = [
            'staticUrl' => $this->staticUrl,
            'content'   => $this->getPersonalizedContent($acceptedLegalDoc),
        ];

        $content = $this->twig->render('/pdf/lender_terms_of_sale.html.twig', $template);

        $this->snappy->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf/style.css');
        $this->snappy->generateFromHtml($content, $this->getPath($acceptedLegalDoc), [], true);
    }

    /**
     * @param int $idTree
     *
     * @throws Exception
     *
     * @return array
     */
    public function getNonPersonalizedContent(int $idTree): array
    {
        return $this->getContent($idTree);
    }

    /**
     * @param AcceptationsLegalDocs $acceptedLegalDoc
     *
     * @throws Exception
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return array
     */
    private function getPersonalizedContent(AcceptationsLegalDocs $acceptedLegalDoc): array
    {
        if (false === $acceptedLegalDoc->getClient()->isLender()) {
            throw new \InvalidArgumentException('Client is no lender');
        }

        $newTermsOfSaleDate = $this->termsOfSaleManager->getDateOfNewTermsOfSaleWithTwoMandates();
        $wallet             = $this->entityManager->getRepository(Wallet::class)->getWalletByType($acceptedLegalDoc->getClient(), WalletType::LENDER);
        $loansCount         = $this->entityManager->getRepository(Loans::class)->getCountLoansForLenderBeforeDate($wallet, $newTermsOfSaleDate);

        $replacements                                     = $acceptedLegalDoc->getClient()->isNaturalPerson() ? $this->getNaturalPersonData($acceptedLegalDoc) : $this->getLegalEntityData($acceptedLegalDoc);
        $content                                          = $this->getContent($acceptedLegalDoc->getIdLegalDoc());
        $content['debtCollectionMandate']                 = $this->replacePlaceHolders($replacements, $content['mandat-de-recouvrement']);
        $content['debtCollectionMandateWithExitingLoans'] = $loansCount > 0 ? $this->replacePlaceHolders($replacements, $content['mandat-de-recouvrement-avec-pret']) : '';

        return $content;
    }

    /**
     * @param AcceptationsLegalDocs $accepted
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return array
     */
    private function getNaturalPersonData(AcceptationsLegalDocs $accepted): array
    {
        $clientAddressRepository = $this->entityManager->getRepository(ClientAddress::class);
        $clientAddress           = $clientAddressRepository->findMainAddressAddedBeforeDate($accepted->getAdded(), $accepted->getClient());

        if (null === $clientAddress) {
            $clientAddress = $clientAddressRepository
                ->findLastModifiedNotArchivedAddressByType($accepted->getClient(), AddressType::TYPE_MAIN_ADDRESS)
            ;
        }

        return [
            '[Civilite]'            => $accepted->getClient()->getTitle(),
            '[Prenom]'              => $accepted->getClient()->getFirstName(),
            '[Nom]'                 => $accepted->getClient()->getLastName(),
            '[date]'                => $accepted->getClient()->getDateOfBirth()->format('d/m/Y'),
            '[ville_naissance]'     => $accepted->getClient()->getBirthCity(),
            '[adresse_fiscale]'     => $clientAddress instanceof ClientAddress ? $clientAddress->getAddress() . ', ' . $clientAddress->getZip() . ', ' . $clientAddress->getCity() . ', ' . $clientAddress->getIdCountry()->getFr() : '',
            '[date_validation_cgv]' => 'Sign&eacute; &eacute;lectroniquement le ' . $accepted->getAdded()->format('d/m/Y'),
        ];
    }

    /**
     * @param AcceptationsLegalDocs $accepted
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return array
     */
    private function getLegalEntityData(AcceptationsLegalDocs $accepted): array
    {
        $company = $this->entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $accepted->getClient()]);
        if (null === $company) {
            throw new \InvalidArgumentException('Client of type legal entity has no attached company');
        }

        $companyAddressRepository = $this->entityManager->getRepository(CompanyAddress::class);
        $companyAddress           = $companyAddressRepository->findMainAddressAddedBeforeDate($accepted->getAdded(), $company);

        if (null === $companyAddress) {
            $companyAddress = $companyAddressRepository->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
        }

        return [
            '[Civilite]'            => $accepted->getClient()->getTitle(),
            '[Prenom]'              => $accepted->getClient()->getFirstName(),
            '[Nom]'                 => $accepted->getClient()->getLastName(),
            '[Fonction]'            => '',
            '[Raison_sociale]'      => $company->getName(),
            '[SIREN]'               => $company->getSiren(),
            '[adresse_fiscale]'     => $companyAddress instanceof CompanyAddress ? $companyAddress->getAddress() . ', ' . $companyAddress->getZip() . ', ' . $companyAddress->getCity() . ', ' . $companyAddress->getIdCountry()->getFr() : '',
            '[date_validation_cgv]' => 'Sign&eacute; &eacute;lectroniquement le ' . $accepted->getAdded()->format('d/m/Y'),
        ];
    }

    /**
     * @param int $idTree
     *
     * @throws Exception
     *
     * @return array
     */
    private function getContent(int $idTree): array
    {
        $tosElements = $this->entityManager->getRepository(TreeElements::class)
            ->findBy(['idTree' => $idTree])
        ;

        if (empty($tosElements)) {
            throw new \InvalidArgumentException('There are not tree elements associated with terms of sales treeId');
        }

        $content           = [];
        $elementRepository = $this->entityManager->getRepository(Elements::class);
        /** @var TreeElements $treeElement */
        foreach ($tosElements as $treeElement) {
            /** @var Elements $element */
            $element = $elementRepository->findOneBy(['idElement' => $treeElement->getIdElement()]);
            if (null === $element) {
                throw new Exception('Tree element has no corresponding element');
            }

            $content[$element->getSlug()] = $treeElement->getValue();
        }

        return $content;
    }

    /**
     * @param array  $placeholders
     * @param string $content
     *
     * @return string
     */
    private function replacePlaceHolders(array $placeholders, string $content): string
    {
        return str_replace(array_keys($placeholders), $placeholders, $content);
    }
}
