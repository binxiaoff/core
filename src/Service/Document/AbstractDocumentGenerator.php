<?php

declare(strict_types=1);

namespace Unilend\Service\Document;

abstract class AbstractDocumentGenerator implements DocumentGeneratorInterface
{
    /** @var string */
    private $documentRootDirectory;

    /**
     * @param string $documentRootDirectory
     */
    public function __construct(string $documentRootDirectory)
    {
        $this->documentRootDirectory = $documentRootDirectory;
    }

    /**
     * @param object $document
     *
     * @return bool
     */
    public function exists(object $document): bool
    {
        return file_exists($this->getFilePath($document));
    }

    /**
     * @param object $document
     *
     * @return string
     */
    public function getFilePath(object $document): string
    {
        return $this->getRootDirectory() . DIRECTORY_SEPARATOR . $this->getRelativeFilePath($document);
    }

    /**
     * @param object $document
     */
    abstract public function generate(object $document): void;

    /**
     * @param object $document
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
        if (false === is_dir($this->documentRootDirectory)) {
            mkdir($this->documentRootDirectory, 0775);
        }

        $rootDirectory = realpath($this->documentRootDirectory);

        return DIRECTORY_SEPARATOR === mb_substr($rootDirectory, -1) ? mb_substr($rootDirectory, 0, -1) : $rootDirectory;
    }

    /**
     * @param object $document
     *
     * @return string
     */
    abstract protected function getFileName(object $document): string;

    /**
     * @param object $document
     *
     * @return string
     */
    abstract protected function getRelativeDirectory(object $document): string;
}
