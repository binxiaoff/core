<?php

declare(strict_types=1);

namespace Unilend\Service\Document;

use Doctrine\Common\Persistence\ManagerRegistry;
use InvalidArgumentException;
use RuntimeException;
use Unilend\Entity\Interfaces\FileStorageInterface;

abstract class AbstractDocumentGenerator
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
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

        return $document->getRelativeFilePath() ?: $this->generateRelativeDirectory($document) . DIRECTORY_SEPARATOR . $this->getFileName($document);
    }

    /**
     * @param FileStorageInterface $document
     */
    final public function generate(FileStorageInterface $document): void
    {
        if (false === $this->supports($document)) {
            throw new InvalidArgumentException(sprintf('The document type %s is not supported by the generator.', get_class($document)));
        }

        $this->generateDocument($document);

        $document->setRelativeFilePath($this->getFilePath($document));

        $entityManager = $this->managerRegistry->getManagerForClass(get_class($document));
        if ($entityManager) {
            $entityManager->persist($document);
            $entityManager->flush();
        } else {
            throw new RuntimeException(sprintf('Cannot find the entity manager for %s', get_class($document)));
        }
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
}
