<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Document;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{AcceptationsLegalDocs, AddressType, ClientAddress, CompanyAddress, Elements, TreeElements, WalletType};
use Knp\Snappy\Pdf;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Unilend\Bundle\CoreBusinessBundle\Service\TermsOfSaleManager;

class LenderTermsOfSaleGenerator implements DocumentGeneratorInterface
{
    const PATH = 'pdf' . DIRECTORY_SEPARATOR . 'cgv_preteurs';

    const LEGAL_ENTITY_PLACEHOLDERS = [
        '[Civilite]',
        '[Prenom]',
        '[Nom]',
        '[Fonction]',
        '[Raison_sociale]',
        '[SIREN]',
        '[adresse_fiscale]',
        '[date_validation_cgv]'
    ];

    const NATURAL_PERSON_PLACEHOLDERS = [
        '[Civilite]',
        '[Prenom]',
        '[Nom]',
        '[date]',
        '[ville_naissance]',
        '[adresse_fiscale]',
        '[date_validation_cgv]'
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
    )
    {
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
     * @return string
     * @throws \Exception
     */
    public function getPath($acceptedLegalDoc): string
    {
        if (false === $acceptedLegalDoc instanceof AcceptationsLegalDocs) {
            $parameterType = gettype($acceptedLegalDoc);
            $parameterType = 'object' === $parameterType ? get_class($acceptedLegalDoc) : $parameterType;

            throw new \InvalidArgumentException('AcceptationsLegalDocs entity expected, got "' . $parameterType . '"');
        }

        return $this->protectedPath . self::PATH . DIRECTORY_SEPARATOR . $acceptedLegalDoc->getIdClient()->getIdClient() . DIRECTORY_SEPARATOR . $this->getName($acceptedLegalDoc);
    }

    /**
     * @param AcceptationsLegalDocs $acceptedLegalDoc
     *
     * @return string
     */
    public function getName(AcceptationsLegalDocs $acceptedLegalDoc)
    {
        return 'cgv_preteurs-' . $acceptedLegalDoc->getIdClient()->getHash() . '-' . $acceptedLegalDoc->getIdLegalDoc() . '.pdf';
    }

    /**
     * @param AcceptationsLegalDocs $acceptedLegalDoc
     *
     * @return bool
     * @throws \Exception
     */
    public function exists($acceptedLegalDoc): bool
    {
        $path = $this->getPath($acceptedLegalDoc);

        return $this->filesystem->exists($path);
    }

    /**
     * @param AcceptationsLegalDocs $acceptedLegalDoc
     *
     * @throws \Exception
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
     * @param AcceptationsLegalDocs $acceptedLegalDoc
     *
     * @return array
     * @throws \Exception
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getPersonalizedContent(AcceptationsLegalDocs $acceptedLegalDoc): array
    {
        if (false === $acceptedLegalDoc->getIdClient()->isLender()) {
            throw new \InvalidArgumentException('Client is no lender');
        }

        $newTermsOfServiceDate        = $this->termsOfSaleManager->getDateOfNewTermsOfSaleWithTwoMandates();
        $wallet                       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($acceptedLegalDoc->getIdClient(), WalletType::LENDER);
        $loansCount                   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->getCountLoansForLenderBeforeDate($wallet, $newTermsOfServiceDate);

        $replacements                                     = $acceptedLegalDoc->getIdClient()->isNaturalPerson() ? $this->getNaturalPersonData($acceptedLegalDoc) : $this->getLegalEntityData($acceptedLegalDoc);
        $content                                          = $this->getContent($acceptedLegalDoc->getIdLegalDoc());
        $content['debtCollectionMandate']                 = $this->replacePlaceHolders($replacements, $content['mandat-de-recouvrement']);
        $content['debtCollectionMandateWithExitingLoans'] = $loansCount > 0 ? $this->replacePlaceHolders($replacements, $content['mandat-de-recouvrement-avec-pret']) : '';

        return $content;
    }

    /**
     * @param AcceptationsLegalDocs $accepted
     *
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getNaturalPersonData(AcceptationsLegalDocs $accepted): array
    {
        $clientAddressRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress');
        $clientAddress           = $clientAddressRepository->findMainAddressAddedBeforeDate($accepted->getAdded(), $accepted->getIdClient());

        if (null === $clientAddress) {
            $clientAddress = $clientAddressRepository
                ->findLastModifiedNotArchivedAddressByType($accepted->getIdClient(), AddressType::TYPE_MAIN_ADDRESS);
        }

        return [
            '[Civilite]'            => $accepted->getIdClient()->getCivilite(),
            '[Prenom]'              => $accepted->getIdClient()->getPrenom(),
            '[Nom]'                 => $accepted->getIdClient()->getNom(),
            '[date]'                => $accepted->getIdClient()->getNaissance()->format('d/m/Y'),
            '[ville_naissance]'     => $accepted->getIdClient()->getVilleNaissance(),
            '[adresse_fiscale]'     => $clientAddress instanceof ClientAddress ? $clientAddress->getAddress() . ', ' . $clientAddress->getZip() . ', ' . $clientAddress->getCity() . ', ' . $clientAddress->getIdCountry()->getFr() : '',
            '[date_validation_cgv]' => 'Sign&eacute; &eacute;lectroniquement le ' . $accepted->getAdded()->format('d/m/Y')
        ];
    }

    /**
     * @param AcceptationsLegalDocs $accepted
     *
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getLegalEntityData(AcceptationsLegalDocs $accepted): array
    {
        $company = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $accepted->getIdClient()]);
        if (null === $company) {
            throw new \InvalidArgumentException('Client of type legal entity has no attached company');
        }

        $companyAddressRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress');
        $companyAddress           = $companyAddressRepository->findMainAddressAddedBeforeDate($accepted->getAdded(), $company);

        if (null === $companyAddress) {
            $companyAddress = $companyAddressRepository->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
        }

        return [
            '[Civilite]'            => $accepted->getIdClient()->getCivilite(),
            '[Prenom]'              => $accepted->getIdClient()->getPrenom(),
            '[Nom]'                 => $accepted->getIdClient()->getNom(),
            '[Fonction]'            => $accepted->getIdClient()->getFonction(),
            '[Raison_sociale]'      => $company->getName(),
            '[SIREN]'               => $company->getSiren(),
            '[adresse_fiscale]'     => $companyAddress instanceof CompanyAddress ? $companyAddress->getAddress() . ', ' . $companyAddress->getZip() . ', ' . $companyAddress->getCity() . ', ' . $companyAddress->getIdCountry()->getFr() : '',
            '[date_validation_cgv]' => 'Sign&eacute; &eacute;lectroniquement le ' . $accepted->getAdded()->format('d/m/Y')
        ];
    }

    /**
     * @param int $idTree
     *
     * @return array
     * @throws \Exception
     */
    private function getContent(int $idTree): array
    {
        $tosElements = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TreeElements')
            ->findBy(['idTree' => $idTree]);

        if (empty($tosElements)) {
            throw new \InvalidArgumentException('There are not tree elements associated with terms of sales treeId');
        }

        $content           = [];
        $elementRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Elements');
        /** @var TreeElements $treeElement */
        foreach ($tosElements as $treeElement) {
            /** @var Elements $element */
            $element = $elementRepository->findOneBy(['idElement' => $treeElement->getIdElement()]);
            if (null === $element) {
                throw new \Exception('Tree element has no corresponding element');
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

    /**
     * @param int    $idTree
     * @param string $type
     *
     * @return array
     * @throws \Exception
     */
    public function getNonPersonalizedContent(int $idTree, string $type): array
    {
        $content = $this->getContent($idTree);

        if (false === empty($type)) {
            $replacements = explode(';', $content['contenu-variables-par-defaut-morale']);
            $content['debtCollectionContract'] = str_replace(self::LEGAL_ENTITY_PLACEHOLDERS, $replacements, $content['mandat-de-recouvrement-personne-morale']);

        } else {
            $replacements = explode(';', $content['contenu-variables-par-defaut']);
            $content['debtCollectionContract'] = str_replace(self::NATURAL_PERSON_PLACEHOLDERS, $replacements, $content['mandat-de-recouvrement']);
        }

        return $content;
    }
}
