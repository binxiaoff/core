<?php

namespace Unilend\Service\Document;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Knp\Snappy\Pdf;
use Psr\Log\LoggerInterface;
use Symfony\Component\{Asset\Packages, Filesystem\Filesystem};
use Twig\Environment;
use Unilend\Entity\{AcceptationsLegalDocs, Elements, TreeElements};
use Unilend\Service\TermsOfSale\TermsOfSaleManager;

class TermsOfSaleGenerator implements DocumentGeneratorInterface
{
    public const PATH = 'pdf' . DIRECTORY_SEPARATOR . 'cgu';

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

            throw new InvalidArgumentException(sprintf('AcceptationsLegalDocs entity expected, got %s', $parameterType));
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
        return 'cgu-' . $acceptedLegalDoc->getClient()->getHash() . '-' . $acceptedLegalDoc->getLegalDoc()->getIdTree() . '.pdf';
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
     */
    public function generate($acceptedLegalDoc): void
    {
        if (false === $acceptedLegalDoc instanceof AcceptationsLegalDocs) {
            $parameterType = gettype($acceptedLegalDoc);
            $parameterType = 'object' === $parameterType ? get_class($acceptedLegalDoc) : $parameterType;

            throw new InvalidArgumentException(sprintf('AcceptationsLegalDocs entity expected, got %s', $parameterType));
        }

        $template = [
            'staticUrl' => $this->staticUrl,
            'content'   => $this->getNonPersonalizedContent($acceptedLegalDoc->getLegalDoc()->getIdTree()),
        ];

        $content = $this->twig->render('/terms_of_sale/pdf/lender_terms_of_sale.html.twig', $template);

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
            throw new InvalidArgumentException('There are not tree elements associated with terms of sales treeId');
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
}
