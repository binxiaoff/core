<?php

declare(strict_types=1);

namespace Unilend\Core\Service\Document;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use RuntimeException;
use Unilend\Core\Entity\Interfaces\FileStorageInterface;

abstract class AbstractDocumentGenerator
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    final public function getFilePath(FileStorageInterface $document): string
    {
        if (false === $this->supports($document)) {
            throw new InvalidArgumentException(\sprintf('The document type %s is not supported by the generator.', \get_class($document)));
        }

        return $document->getRelativeFilePath() ?: $this->generateRelativeDirectory($document) . DIRECTORY_SEPARATOR . $this->getFileName($document);
    }

    final public function generate(FileStorageInterface $document): void
    {
        if (false === $this->supports($document)) {
            throw new InvalidArgumentException(\sprintf('The document type %s is not supported by the generator.', \get_class($document)));
        }

        $this->generateDocument($document);

        $document->setRelativeFilePath($this->getFilePath($document));

        $entityManager = $this->managerRegistry->getManagerForClass(\get_class($document));
        if ($entityManager) {
            $entityManager->persist($document);
            $entityManager->flush();
        } else {
            throw new RuntimeException(\sprintf('Cannot find the entity manager for %s', \get_class($document)));
        }
    }

    abstract protected function generateDocument(FileStorageInterface $document): void;

    abstract protected function getFileName(FileStorageInterface $document): string;

    abstract protected function generateRelativeDirectory(FileStorageInterface $document): string;

    /**
     * Determines if the document are supported by this generator.
     */
    abstract protected function supports(FileStorageInterface $document): bool;
}
