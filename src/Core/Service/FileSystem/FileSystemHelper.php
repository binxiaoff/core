<?php

declare(strict_types=1);

namespace Unilend\Core\Service\FileSystem;

use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Exception;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Unilend\Core\Entity\FileVersion;

use function Symfony\Component\String\s;

class FileSystemHelper
{
    private const ENCRYPTED_FILE_SUFFIX = '-encrypted';

    private FileCrypto $fileCrypto;
    private FilesystemOperator $userAttachmentFilesystem;
    private FilesystemOperator $generatedDocumentFilesystem;

    public function __construct(FileCrypto $fileCrypto, FilesystemOperator $userAttachmentFilesystem, FilesystemOperator $generatedDocumentFilesystem)
    {
        $this->fileCrypto                  = $fileCrypto;
        $this->userAttachmentFilesystem    = $userAttachmentFilesystem;
        $this->generatedDocumentFilesystem = $generatedDocumentFilesystem;
    }

    /**
     * @throws EnvironmentIsBrokenException|IOException|FilesystemException
     */
    public function writeTempFileToFileSystem(string $temporaryFilePath, FilesystemOperator $filesystem, string $filesystemDestPath, bool $encryption = true): ?string
    {
        $key      = null;
        $filePath = $temporaryFilePath;

        if ($encryption) {
            $filePath = $temporaryFilePath . self::ENCRYPTED_FILE_SUFFIX;
            $key      = $this->fileCrypto->encryptFile($temporaryFilePath, $filePath);
        }

        $fileResource = @fopen($filePath, 'r+b');

        if (is_resource($fileResource)) {
            $filesystem->writeStream($filesystemDestPath, $fileResource);
            fclose($fileResource);
        }

        /* We delete only the temporary file that we created for the encryption.
         * The orignal file ($temporaryFilePath) is managed by other module (for example, Symfony file system), which should not be touched.
        */
        if ($encryption) {
            @unlink($filePath);
        }

        return $key;
    }

    /**
     * @throws Exception
     */
    public function getFileSystem(FileVersion $fileVersion): FilesystemOperator
    {
        switch ($fileVersion->getFileSystem()) {
            case FileVersion::FILE_SYSTEM_USER_ATTACHMENT:
                $filesystem = $this->userAttachmentFilesystem;

                break;

            case FileVersion::FILE_SYSTEM_GENERATED_DOCUMENT:
                $filesystem = $this->generatedDocumentFilesystem;

                break;

            default:
                throw new RuntimeException(sprintf('The filesystem %s is not be supported', $fileVersion->getFileSystem()));
        }

        return $filesystem;
    }

    /**
     * @throws Exception|FilesystemException
     *
     * @return false|resource
     */
    public function readStream(FileVersion $fileVersion)
    {
        $fileSystem   = $this->getFileSystem($fileVersion);
        $fileResource = $fileSystem->readStream($fileVersion->getPath());

        if ($fileResource && $fileVersion->getPlainEncryptionKey()) {
            $fileResource = $this->decrypt($fileResource, $fileVersion->getPlainEncryptionKey());
        }

        return $fileResource;
    }

    public function normalizeFileName(string $fileName): string
    {
        $pathInfo  = pathinfo($fileName);
        $fileName  = s(trim($pathInfo['filename']))->ascii()->snake()->toString();
        $extension = s(trim($pathInfo['extension'] ?? ''))->ascii()->snake()->toString();

        return $fileName . ($extension ? '.' . $extension : '');
    }

    /**
     * @param resource $fileResource
     *
     * @throws IOException|WrongKeyOrModifiedCiphertextException|BadFormatException|EnvironmentIsBrokenException
     *
     * @return false|resource
     */
    private function decrypt($fileResource, string $key)
    {
        $outputFileResource = tmpfile();
        $this->fileCrypto->decryptFileResource($fileResource, $outputFileResource, $key);

        // Re-open the file to change the resource mode, so that the mode is the same as FilesystemOperator::readStream
        return fopen(stream_get_meta_data($outputFileResource)['uri'], 'rb');
    }
}
