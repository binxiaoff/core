<?php

namespace Unilend\Service\Document;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Knp\Snappy\Pdf;
use Symfony\Component\{Asset\Packages, Filesystem\Filesystem};
use Twig\Environment;
use Unilend\Entity\{AcceptationsLegalDocs, Elements, TreeElements};

class ServiceTermsGenerator implements DocumentGeneratorInterface
{
    public const PATH = 'pdf' . DIRECTORY_SEPARATOR . 'service_terms';

    /** @var EntityManagerInterface */
    private $entityManager;
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

    /**
     * @param EntityManagerInterface $entityManager
     * @param Filesystem             $filesystem
     * @param string                 $protectedPath
     * @param string                 $staticPath
     * @param Environment            $twig
     * @param Pdf                    $snappy
     * @param Packages               $assetsPackages
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Filesystem $filesystem,
        string $protectedPath,
        string $staticPath,
        Environment $twig,
        Pdf $snappy,
        Packages $assetsPackages
    ) {
        $this->entityManager = $entityManager;
        $this->filesystem    = $filesystem;
        $this->protectedPath = $protectedPath;
        $this->staticPath    = $staticPath;
        $this->twig          = $twig;
        $this->snappy        = $snappy;
        $this->staticUrl     = $assetsPackages->getUrl('');

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
        return 'conditions-service-' . $acceptedLegalDoc->getClient()->getHash() . '-' . $acceptedLegalDoc->getLegalDoc()->getIdTree() . '.pdf';
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

        $content = $this->twig->render('/service_terms/pdf/service_terms.html.twig', $template);

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
        $serviceTermsElements = $this->entityManager->getRepository(TreeElements::class)
            ->findBy(['idTree' => $idTree])
        ;

        if (empty($serviceTermsElements)) {
            throw new InvalidArgumentException('There are not tree elements associated with terms of sales treeId');
        }

        $content           = [];
        $elementRepository = $this->entityManager->getRepository(Elements::class);
        /** @var TreeElements $treeElement */
        foreach ($serviceTermsElements as $treeElement) {
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
