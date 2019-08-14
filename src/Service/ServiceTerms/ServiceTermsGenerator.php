<?php

declare(strict_types=1);

namespace Unilend\Service\ServiceTerms;

use Doctrine\Common\Persistence\ManagerRegistry;
use Exception;
use Knp\Snappy\Pdf;
use Symfony\Component\Asset\Packages;
use Twig\Environment;
use Unilend\Entity\{AcceptationsLegalDocs, Interfaces\FileStorageInterface};
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

    /**
     * @param string          $documentRootDirectory
     * @param string          $publicDirectory
     * @param Environment     $twig
     * @param Pdf             $snappy
     * @param Packages        $assetsPackages
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(
        string $documentRootDirectory,
        string $publicDirectory,
        Environment $twig,
        Pdf $snappy,
        Packages $assetsPackages,
        ManagerRegistry $managerRegistry
    ) {
        $this->publicDirectory = $publicDirectory;
        $this->twig            = $twig;
        $this->snappy          = $snappy;
        $this->staticUrl       = $assetsPackages->getUrl('');

        $this->snappy->setBinary('/usr/local/bin/wkhtmltopdf');

        parent::__construct($documentRootDirectory, $managerRegistry);
    }

    /**
     * @param AcceptationsLegalDocs|FileStorageInterface $acceptedLegalDoc
     *
     * @throws Exception
     */
    protected function generateDocument(FileStorageInterface $acceptedLegalDoc): void
    {
        $content = $this->twig->render('/service_terms/pdf/service_terms.html.twig', ['content' => $acceptedLegalDoc->getLegalDoc()->getContent()]);

        $this->snappy->setOption('user-style-sheet', $this->publicDirectory . 'styles/pdf/style.css');
        $this->snappy->generateFromHtml($content, $this->getFilePath($acceptedLegalDoc), [], true);
    }

    /**
     * @param AcceptationsLegalDocs|FileStorageInterface $acceptedLegalDoc
     *
     * @return string
     */
    protected function getFileName(FileStorageInterface $acceptedLegalDoc): string
    {
        return self::FILE_PREFIX . '-' . $acceptedLegalDoc->getClient()->getHash() . '-' . $acceptedLegalDoc->getLegalDoc()->getId() . '.pdf';
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
