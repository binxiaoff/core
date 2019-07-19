<?php

namespace Unilend\Service\ServiceTerms;

use Exception;
use InvalidArgumentException;
use Knp\Snappy\Pdf;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Asset\Packages;
use Twig\Environment;
use Unilend\Entity\{AcceptationsLegalDocs, Elements, Interfaces\FileStorageInterface, TreeElements};
use Unilend\Repository\{AcceptationLegalDocsRepository, ElementsRepository, TreeElementsRepository};
use Unilend\Service\Document\AbstractDocumentGenerator;

class ServiceTermsGenerator extends AbstractDocumentGenerator
{
    private const PATH        = 'service_terms';
    private const FILE_PREFIX = 'conditions-service';

    /** @var Environment */
    private $twig;
    /** @var Pdf */
    private $snappy;
    /** @var string */
    private $staticUrl;
    /** @var string */
    private $publicDirectory;
    /** @var AcceptationLegalDocsRepository */
    private $acceptationLegalDocsRepository;
    /** @var TreeElementsRepository */
    private $treeElementsRepository;
    /** @var ElementsRepository */
    private $elementsRepository;

    /**
     * @param string                         $documentRootDirectory
     * @param string                         $publicDirectory
     * @param Environment                    $twig
     * @param Pdf                            $snappy
     * @param Packages                       $assetsPackages
     * @param AcceptationLegalDocsRepository $acceptationLegalDocsRepository
     * @param TreeElementsRepository         $treeElementsRepository
     * @param ElementsRepository             $elementsRepository
     * @param ManagerRegistry                $managerRegistry
     */
    public function __construct(
        string $documentRootDirectory,
        string $publicDirectory,
        Environment $twig,
        Pdf $snappy,
        Packages $assetsPackages,
        AcceptationLegalDocsRepository $acceptationLegalDocsRepository,
        TreeElementsRepository $treeElementsRepository,
        ElementsRepository $elementsRepository,
        ManagerRegistry $managerRegistry
    ) {
        $this->publicDirectory                = $publicDirectory;
        $this->twig                           = $twig;
        $this->snappy                         = $snappy;
        $this->staticUrl                      = $assetsPackages->getUrl('');
        $this->acceptationLegalDocsRepository = $acceptationLegalDocsRepository;
        $this->treeElementsRepository         = $treeElementsRepository;
        $this->elementsRepository             = $elementsRepository;

        $this->snappy->setBinary('/usr/local/bin/wkhtmltopdf');

        parent::__construct($documentRootDirectory, $managerRegistry);
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
        $serviceTermsElements = $this->treeElementsRepository->findBy(['idTree' => $idTree]);

        if (empty($serviceTermsElements)) {
            throw new InvalidArgumentException('There are not tree elements associated with terms of sales treeId');
        }

        $content = [];
        /** @var TreeElements $treeElement */
        foreach ($serviceTermsElements as $treeElement) {
            /** @var Elements $element */
            $element = $this->elementsRepository->findOneBy(['idElement' => $treeElement->getIdElement()]);
            if (null === $element) {
                throw new Exception('Tree element has no corresponding element');
            }

            $content[$element->getSlug()] = $treeElement->getValue();
        }

        return $content;
    }

    /**
     * @param AcceptationsLegalDocs|FileStorageInterface $acceptedLegalDoc
     *
     * @throws Exception
     */
    protected function generateDocument(FileStorageInterface $acceptedLegalDoc): void
    {
        $template = [
            'staticUrl' => $this->staticUrl,
            'content'   => $this->getNonPersonalizedContent($acceptedLegalDoc->getLegalDoc()->getIdTree()),
        ];

        $content = $this->twig->render('/service_terms/pdf/service_terms.html.twig', $template);

        $this->snappy->setOption('user-style-sheet', $this->publicDirectory . 'styles/default/pdf/style.css');
        $this->snappy->generateFromHtml($content, $this->getFilePath($acceptedLegalDoc), [], true);
    }

    /**
     * @param AcceptationsLegalDocs|FileStorageInterface $acceptedLegalDoc
     *
     * @return string
     */
    protected function getFileName(FileStorageInterface $acceptedLegalDoc): string
    {
        return self::FILE_PREFIX . '-' . $acceptedLegalDoc->getClient()->getHash() . '-' . $acceptedLegalDoc->getLegalDoc()->getIdTree() . '.pdf';
    }

    /**
     * @param AcceptationsLegalDocs|FileStorageInterface $acceptedLegalDoc
     *
     * @return string
     */
    protected function generateRelativeDirectory(FileStorageInterface $acceptedLegalDoc): string
    {
        return self::PATH . DIRECTORY_SEPARATOR . $acceptedLegalDoc->getClient()->getIdClient();
    }

    /**
     * {@inheritdoc}
     */
    protected function supports(FileStorageInterface $document): bool
    {
        return $document instanceof AcceptationsLegalDocs;
    }
}
