<?php

namespace Unilend\Service\ServiceTerms;

use Exception;
use InvalidArgumentException;
use Knp\Snappy\Pdf;
use Symfony\Component\{Asset\Packages, Filesystem\Filesystem};
use Twig\Environment;
use Unilend\Entity\{AcceptationsLegalDocs, Elements, TreeElements};
use Unilend\Repository\AcceptationLegalDocsRepository;
use Unilend\Repository\ElementsRepository;
use Unilend\Repository\TreeElementsRepository;
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
     */
    public function __construct(
        string $documentRootDirectory,
        string $publicDirectory,
        Environment $twig,
        Pdf $snappy,
        Packages $assetsPackages,
        AcceptationLegalDocsRepository $acceptationLegalDocsRepository,
        TreeElementsRepository $treeElementsRepository,
        ElementsRepository $elementsRepository
    ) {
        $this->publicDirectory                = $publicDirectory;
        $this->twig                           = $twig;
        $this->snappy                         = $snappy;
        $this->staticUrl                      = $assetsPackages->getUrl('');
        $this->acceptationLegalDocsRepository = $acceptationLegalDocsRepository;
        $this->treeElementsRepository         = $treeElementsRepository;
        $this->elementsRepository             = $elementsRepository;

        $this->snappy->setBinary('/usr/local/bin/wkhtmltopdf');

        parent::__construct($documentRootDirectory);
    }

    /**
     * @param AcceptationsLegalDocs|object $acceptedLegalDoc
     *
     * @throws Exception
     */
    public function generate(object $acceptedLegalDoc): void
    {
        $this->checkObject($acceptedLegalDoc);

        $template = [
            'staticUrl' => $this->staticUrl,
            'content'   => $this->getNonPersonalizedContent($acceptedLegalDoc->getLegalDoc()->getIdTree()),
        ];

        $content = $this->twig->render('/service_terms/pdf/service_terms.html.twig', $template);

        $this->snappy->setOption('user-style-sheet', $this->publicDirectory . 'styles/default/pdf/style.css');
        $this->snappy->generateFromHtml($content, $this->getFilePath($acceptedLegalDoc), [], true);

        $acceptedLegalDoc->setPdfName($this->getRelativeFilePath($acceptedLegalDoc));
        $this->acceptationLegalDocsRepository->save($acceptedLegalDoc);
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
     * @param AcceptationsLegalDocs|object $acceptedLegalDoc
     *
     * @return string
     */
    protected function getFileName(object $acceptedLegalDoc): string
    {
        $this->checkObject($acceptedLegalDoc);

        return self::FILE_PREFIX . '-' . $acceptedLegalDoc->getClient()->getHash() . '-' . $acceptedLegalDoc->getLegalDoc()->getIdTree() . '.pdf';
    }

    /**
     * @param AcceptationsLegalDocs|object $acceptedLegalDoc
     *
     * @return string
     */
    protected function getRelativeDirectory(object $acceptedLegalDoc): string
    {
        $this->checkObject($acceptedLegalDoc);

        return self::PATH . DIRECTORY_SEPARATOR . $acceptedLegalDoc->getClient()->getIdClient();
    }

    /**
     * @param AcceptationsLegalDocs|object $acceptedLegalDoc
     *
     * @return string
     */
    protected function getRelativeFilePath(object $acceptedLegalDoc): string
    {
        $this->checkObject($acceptedLegalDoc);

        return $acceptedLegalDoc->getPdfName() ?? $this->getRelativeDirectory($acceptedLegalDoc) . DIRECTORY_SEPARATOR . $this->getFileName($acceptedLegalDoc);
    }

    /**
     * @param AcceptationsLegalDocs $acceptationsLegalDocs
     */
    private function checkObject(AcceptationsLegalDocs $acceptationsLegalDocs)
    {
        //nothing to do. The language structure (type hint) is used to check the object.
    }
}
