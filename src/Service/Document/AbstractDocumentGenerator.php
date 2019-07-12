<?php

declare(strict_types=1);

namespace Unilend\Service\Document;

use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractDocumentGenerator implements DocumentGeneratorInterface
{
    protected const CONTENT_TYPE_PDF = 'application/pdf';

    /** @var Filesystem */
    private $filesystem;
    /** @var string */
    private $documentRootDirectory;

    /**
     * @param Filesystem $filesystem
     * @param string     $documentRootDirectory
     */
    public function __construct(Filesystem $filesystem, string $documentRootDirectory)
    {
        $this->filesystem            = $filesystem;
        $this->documentRootDirectory = $documentRootDirectory;
    }

    /**
     * @param $document
     *
     * @return bool
     */
    public function exists(object $document): bool
    {
        return $this->filesystem->exists($this->getFilePath($document));
    }

    /**
     * @param $document
     *
     * @return string
     */
    public function getFilePath(object $document): string
    {
        return $this->getRootDirectory() . DIRECTORY_SEPARATOR . $this->getRelativeFilePath($document);
    }

    /**
     * @param $document
     *
     * @return string
     */
    protected function getRelativeFilePath(object $document): string
    {
        return $this->getRelativeDirectory($document) . DIRECTORY_SEPARATOR . $this->getFileName($document);
    }

    /**
     * @return string
     */
    protected function getRootDirectory()
    {
        $rootDirectory = realpath($this->documentRootDirectory);

        return DIRECTORY_SEPARATOR === mb_substr($rootDirectory, -1) ? mb_substr($rootDirectory, 0, -1) : $rootDirectory;
    }

    /**
     * @param $document
     */
    abstract public function generate(object $document): void;

    /**
     * @param $document
     *
     * @return string
     */
    abstract protected function getFileName(object $document): string;

    /**
     * @param $document
     *
     * @return string
     */
    abstract protected function getRelativeDirectory(object $document): string;
}
