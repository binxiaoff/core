<?php

declare(strict_types=1);

namespace Unilend\Service\FileSystem;

use Doctrine\ORM\Proxy\Proxy;
use Exception;
use League\Flysystem\{FileExistsException, FileNotFoundException, FilesystemInterface};
use LogicException;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\{ResponseHeaderBag, StreamedResponse};
use Unilend\Entity\{AcceptationsLegalDocs, Attachment};
use URLify;

class FileSystemHelper
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string              $temporaryFilePath
     * @param FilesystemInterface $filesystem
     * @param string              $filesystemDestPath
     *
     * @throws FileExistsException
     */
    public function writeTempFileToFileSystem(string $temporaryFilePath, FilesystemInterface $filesystem, string $filesystemDestPath): void
    {
        $fileResource = @fopen($temporaryFilePath, 'r+b');

        if (is_resource($fileResource)) {
            $result = $filesystem->writeStream($filesystemDestPath, $fileResource);
            fclose($fileResource);

            if (false === $result) {
                throw new RuntimeException(sprintf('Could not write file "%s"', $temporaryFilePath));
            }
        }

        @unlink($temporaryFilePath);
    }

    /**
     * @param FilesystemInterface $filesystem
     * @param string              $filePath
     * @param string|null         $fileName
     *
     * @throws FileNotFoundException
     *
     * @return StreamedResponse
     */
    public function download(FilesystemInterface $filesystem, string $filePath, ?string $fileName = null): StreamedResponse
    {
        $response = new StreamedResponse(static function () use ($filePath, $filesystem) {
            stream_copy_to_stream($filesystem->readStream($filePath), fopen('php://output', 'w+b'));
        });

        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, URLify::downcode($fileName ?? pathinfo($filePath, PATHINFO_FILENAME)));
        $response->headers->set('Content-Disposition', $contentDisposition);
        $response->headers->set('Content-Type', $filesystem->getMimetype($filePath) ?: 'application/octet-stream');

        return $response;
    }

    /**
     * @param string|object $class
     *
     * @throws Exception
     *
     * @return object|null
     */
    public function getFileSystemForClass($class)
    {
        if (is_object($class)) {
            $class = $class instanceof Proxy ? get_parent_class($class) : get_class($class);
        }
        switch ($class) {
            case Attachment::class:
                return $this->getService('League\Flysystem\UserAttachmentFilesystem');
            case AcceptationsLegalDocs::class:
                return $this->getService('League\Flysystem\GeneratedDocumentFilesystem');
            default:
                throw new LogicException('This code should not be reached');
        }
    }

    /**
     * @param string $name
     *
     * @throws Exception
     *
     * @return object|null
     */
    private function getService(string $name)
    {
        return $this->container->get($name);
    }
}
