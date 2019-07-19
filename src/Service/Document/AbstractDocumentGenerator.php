<?php

declare(strict_types=1);

namespace Unilend\Service\Document;

use InvalidArgumentException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\Interfaces\FileStorageInterface;

abstract class AbstractDocumentGenerator
{
    /** @var string */
    private $documentRootDirectory;
    /** @var ManagerRegistry */
    private $managerRegistry;

    /**
     * @param string          $documentRootDirectory
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(string $documentRootDirectory, ManagerRegistry $managerRegistry)
    {
        $this->documentRootDirectory = $documentRootDirectory;
        $this->managerRegistry       = $managerRegistry;
    }

    /**
     * @param FileStorageInterface $document
     *
     * @return string
     */
    final public function getFilePath(FileStorageInterface $document): string
    {
        if (false === $this->supports($document)) {
            throw new InvalidArgumentException(sprintf('The document type %s is not supported by the generator.', get_class($document)));
        }

        return $this->getRootDirectory() . DIRECTORY_SEPARATOR . $this->getRelativeFilePath($document);
    }

    /**
     * @param FileStorageInterface $document
     */
    final public function generate(FileStorageInterface $document): void
    {
        if (false === $this->supports($document)) {
            throw new InvalidArgumentException(sprintf('The document type %s is not supported by the generator.', get_class($document)));
        }

        if (file_exists($this->getFilePath($document))) {
            return;
        }

        $this->generateDocument($document);

        $document->setRelativeFilePath($this->getRelativeFilePath($document));

        $entityManager = $this->managerRegistry->getManagerForClass(get_class($document));
        $entityManager->persist($document);
        $entityManager->flush();
    }

    /**
     * @param FileStorageInterface $document
     */
    abstract protected function generateDocument(FileStorageInterface $document): void;

    /**
     * @param FileStorageInterface $document
     *
     * @return string
     */
    abstract protected function getFileName(FileStorageInterface $document): string;

    /**
     * @param FileStorageInterface $document
     *
     * @return string
     */
    abstract protected function generateRelativeDirectory(FileStorageInterface $document): string;

    /**
     * Determines if the document are supported by this generator.
     *
     * @param FileStorageInterface $document
     *
     * @return bool
     */
    abstract protected function supports(FileStorageInterface $document): bool;

    /**
     * @param FileStorageInterface $document
     *
     * @return string
     */
    private function getRelativeFilePath(FileStorageInterface $document): string
    {
        return $document->getRelativeFilePath() ?: $this->generateRelativeDirectory($document) . DIRECTORY_SEPARATOR . $this->getFileName($document);
    }

    /**
     * @return string
     */
    private function getRootDirectory()
    {
        if (false === is_dir($this->documentRootDirectory)) {
            mkdir($this->documentRootDirectory, 0775);
        }

        $rootDirectory = realpath($this->documentRootDirectory);

        return DIRECTORY_SEPARATOR === mb_substr($rootDirectory, -1) ? mb_substr($rootDirectory, 0, -1) : $rootDirectory;
    }
}
