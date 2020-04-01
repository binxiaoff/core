<?php

declare(strict_types=1);

namespace Unilend\Service\File;

use Defuse\Crypto\Exception\{EnvironmentIsBrokenException, IOException};
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use InvalidArgumentException;
use League\Flysystem\{FileExistsException, FilesystemInterface};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\{Clients, File, FileVersion, Staff};
use Unilend\Repository\FileRepository;
use Unilend\Service\FileSystem\FileSystemHelper;

class FileUploadManager
{
    /** @var FileSystemHelper */
    private $fileSystemHelper;
    /** @var FilesystemInterface */
    private $userAttachmentFilesystem;
    /** @var FileRepository */
    private $fileRepository;

    /**
     * @param FileSystemHelper    $fileSystemHelper
     * @param FilesystemInterface $userAttachmentFilesystem
     * @param FileRepository      $fileRepository
     */
    public function __construct(FileSystemHelper $fileSystemHelper, FilesystemInterface $userAttachmentFilesystem, FileRepository $fileRepository)
    {
        $this->fileSystemHelper         = $fileSystemHelper;
        $this->userAttachmentFilesystem = $userAttachmentFilesystem;
        $this->fileRepository           = $fileRepository;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param Staff        $uploader
     * @param File|null    $file
     * @param string|null  $description
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     * @throws FileExistsException
     *
     * @return File
     */
    public function upload(UploadedFile $uploadedFile, Staff $uploader, ?File $file = null, string $description = null): File
    {
        $mineType                               = $uploadedFile->getMimeType();
        [$relativeUploadedPath, $encryptionKey] = $this->uploadFile($uploadedFile, $this->userAttachmentFilesystem, '/', $this->getClientDirectory($uploader->getClient()));

        if (null === $file) {
            $file = new File();
        }

        $fileVersion = new FileVersion($relativeUploadedPath, $uploader, $file, FileVersion::FILE_SYSTEM_USER_ATTACHMENT, $encryptionKey, $mineType);
        $fileVersion
            ->setOriginalName($this->fileSystemHelper->normalizeFileName($uploadedFile->getClientOriginalName()))
            ->setSize($uploadedFile->getSize())
        ;

        $file
            ->setCurrentFileVersion($fileVersion)
            ->setDescription($description)
        ;

        $this->fileRepository->save($file);

        return $file;
    }

    /**
     * @param UploadedFile        $file
     * @param FilesystemInterface $filesystem
     * @param string              $uploadRootDirectory
     * @param string|null         $subdirectory
     * @param bool                $encryption
     *
     * @throws EnvironmentIsBrokenException
     * @throws FileExistsException
     * @throws IOException
     *
     * @return array
     */
    private function uploadFile(UploadedFile $file, FilesystemInterface $filesystem, string $uploadRootDirectory, ?string $subdirectory = null, bool $encryption = true): array
    {
        $hash         = hash('sha256', $subdirectory ?? uniqid('', true));
        $subdirectory = $hash[0] . DIRECTORY_SEPARATOR . $hash[1] . ($subdirectory ? DIRECTORY_SEPARATOR . $subdirectory : '');

        $uploadRootDirectory = $this->normalizePath($uploadRootDirectory);
        $uploadDirectory     = $uploadRootDirectory . DIRECTORY_SEPARATOR . $subdirectory;

        $filename = $this->generateFileName($file, $filesystem, $uploadDirectory);
        $filePath = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

        $key = $this->fileSystemHelper->writeTempFileToFileSystem($file->getPathname(), $filesystem, $filePath, $encryption);

        return [$filePath, $key];
    }

    /**
     * @param UploadedFile        $uploadedFile
     * @param FilesystemInterface $filesystem
     * @param string              $uploadDirectory
     *
     * @return string
     */
    private function generateFileName(UploadedFile $uploadedFile, FilesystemInterface $filesystem, string $uploadDirectory): string
    {
        $originalFilename      = $this->fileSystemHelper->normalizeFileName(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));
        $fileNameWithExtension = $originalFilename . '-' . uniqid('', true) . '.' . $uploadedFile->guessExtension() ?? $uploadedFile->getClientOriginalExtension();

        if ($filesystem->has($uploadDirectory . DIRECTORY_SEPARATOR . $fileNameWithExtension)) {
            $fileNameWithExtension = $this->generateFileName($uploadedFile, $filesystem, $uploadDirectory);
        }

        return $fileNameWithExtension;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function normalizePath(string $path): string
    {
        return DIRECTORY_SEPARATOR === mb_substr($path, -1) ? mb_substr($path, 0, -1) : $path;
    }

    /**
     * @param Clients $client
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    private function getClientDirectory(Clients $client): string
    {
        if (empty($client->getId())) {
            throw new InvalidArgumentException('Cannot find the upload destination. The client id is empty.');
        }

        return (string) $client->getId();
    }
}
