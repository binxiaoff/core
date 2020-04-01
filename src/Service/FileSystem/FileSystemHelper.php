<?php

declare(strict_types=1);

namespace Unilend\Service\FileSystem;

use Defuse\Crypto\Exception\{BadFormatException, EnvironmentIsBrokenException, IOException, WrongKeyOrModifiedCiphertextException};
use Doctrine\ORM\Proxy\Proxy;
use Exception;
use League\Flysystem\{FileExistsException, FilesystemInterface};
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Unilend\Entity\{AcceptationsLegalDocs, FileVersion};

class FileSystemHelper
{
    private const ENCRYPTED_FILE_SUFFIX = '-encrypted';

    /** @var ContainerInterface */
    private $container;
    /** @var FileCrypto */
    private $fileCrypto;

    /**
     * @param ContainerInterface $container
     * @param FileCrypto         $fileCrypto
     */
    public function __construct(ContainerInterface $container, FileCrypto $fileCrypto)
    {
        $this->container  = $container;
        $this->fileCrypto = $fileCrypto;
    }

    /**
     * @param string              $temporaryFilePath
     * @param FilesystemInterface $filesystem
     * @param string              $filesystemDestPath
     * @param bool                $encryption
     *
     * @throws FileExistsException
     * @throws EnvironmentIsBrokenException
     * @throws IOException
     *
     * @return string|null
     */
    public function writeTempFileToFileSystem(string $temporaryFilePath, FilesystemInterface $filesystem, string $filesystemDestPath, bool $encryption = true): ?string
    {
        $key      = null;
        $filePath = $temporaryFilePath;

        if ($encryption) {
            $filePath = $temporaryFilePath . self::ENCRYPTED_FILE_SUFFIX;
            $key      = $this->fileCrypto->encryptFile($temporaryFilePath, $filePath);
            @unlink($temporaryFilePath);
        }

        $fileResource = @fopen($filePath, 'r+b');

        if (is_resource($fileResource)) {
            $result = $filesystem->writeStream($filesystemDestPath, $fileResource);
            fclose($fileResource);

            if (false === $result) {
                throw new RuntimeException(sprintf('Could not write file "%s"', $filePath));
            }
        }

        @unlink($filePath);

        return $key;
    }

    /**
     * @param string|object $class
     *
     * @throws Exception
     *
     * @return FilesystemInterface
     */
    public function getFileSystemForClass($class): FilesystemInterface
    {
        if (is_object($class)) {
            $class = $class instanceof Proxy ? get_parent_class($class) : get_class($class);
        }

        $filesystem = null;
        switch ($class) {
            case AcceptationsLegalDocs::class:
                $serviceId = 'League\Flysystem\GeneratedDocumentFilesystem';

                break;
            case FileVersion::class:
                switch ($class->getFileSystem()) {
                    case FileVersion::FILE_SYSTEM_USER_ATTACHMENT:
                        $serviceId = 'League\Flysystem\UserAttachmentFilesystem';

                        break;
                    case FileVersion::FILE_SYSTEM_GENERATED_DOCUMENT:
                        $serviceId = 'League\Flysystem\GeneratedDocumentFilesystem';

                        break;
                    default:
                        throw new RuntimeException(sprintf('The filesystem %s is not be supported', $class->getFileSystem()));
                }

                break;
            default:
                throw new RuntimeException(sprintf('The class %s is not be supported', $class));
        }

        $filesystem = $this->getService($serviceId);

        if ($filesystem instanceof FilesystemInterface) {
            // Do like this, so that the IDE can analyse easier the code.
            return $filesystem;
        }

        throw new RuntimeException(sprintf('Cannot find the filesystem by class %s. Please check the services configurations', $class));
    }

    /**
     * @param FileVersion $attachment
     *
     * @throws Exception
     *
     * @return false|resource
     */
    public function readStream(FileVersion $attachment)
    {
        $fileSystem = $this->getFileSystemForClass($attachment);

        if (!$fileSystem) {
            return false;
        }

        $fileResource = $fileSystem->readStream($attachment->getPath());

        if ($fileResource && $attachment->getPlainEncryptionKey()) {
            $fileResource = $this->decrypt($fileResource, $attachment->getPlainEncryptionKey());
        }

        return $fileResource;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public function normalizeFileName(string $fileName): string
    {
        return \URLify::downcode($fileName);
    }

    /**
     * @param resource $fileResource
     * @param string   $key
     *
     * @throws IOException
     * @throws WrongKeyOrModifiedCiphertextException
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     *
     * @return false|resource
     */
    private function decrypt($fileResource, string $key)
    {
        $outputFileResource = tmpfile();
        $this->fileCrypto->decryptFileResource($fileResource, $outputFileResource, $key);

        // Re-open the file to change the resource mode, so that the mode is the same as FilesystemInterface::readStream
        return fopen(stream_get_meta_data($outputFileResource)['uri'], 'rb');
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
