<?php

declare(strict_types=1);

namespace Unilend\Core\Service\File;

use Defuse\Crypto\Exception\{EnvironmentIsBrokenException, IOException};
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use InvalidArgumentException;
use League\Flysystem\{FileExistsException, FilesystemInterface};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\FileVersion;
use Unilend\Core\Entity\User;
use Unilend\Core\Entity\{Company, Staff};
use Unilend\Core\Message\File\FileUploaded;
use Unilend\Core\Repository\FileRepository;
use Unilend\Core\Service\FileSystem\FileSystemHelper;

class FileUploadManager
{
    /** @var FileSystemHelper */
    private $fileSystemHelper;
    /** @var FilesystemInterface */
    private $userAttachmentFilesystem;
    /** @var FileRepository */
    private $fileRepository;
    /** @var MessageBusInterface */
    private $messageBus;

    /**
     * @param FileSystemHelper    $fileSystemHelper
     * @param FilesystemInterface $userAttachmentFilesystem
     * @param FileRepository      $fileRepository
     * @param MessageBusInterface $messageBus
     */
    public function __construct(FileSystemHelper $fileSystemHelper, FilesystemInterface $userAttachmentFilesystem, FileRepository $fileRepository, MessageBusInterface $messageBus)
    {
        $this->fileSystemHelper         = $fileSystemHelper;
        $this->userAttachmentFilesystem = $userAttachmentFilesystem;
        $this->fileRepository           = $fileRepository;
        $this->messageBus               = $messageBus;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param User         $uploader
     * @param File|null    $file
     * @param array        $context
     * @param Company|null $company
     *
     * @throws EnvironmentIsBrokenException
     * @throws FileExistsException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function upload(UploadedFile $uploadedFile, User $uploader, File $file, array $context = [], ?Company $company = null): void
    {
        $mineType                               = $uploadedFile->getMimeType();
        [$relativeUploadedPath, $encryptionKey] = $this->uploadFile($uploadedFile, $this->userAttachmentFilesystem, '/', $this->getUserDirectory($uploader));

        $fileVersion = new FileVersion($relativeUploadedPath, $uploader, $file, FileVersion::FILE_SYSTEM_USER_ATTACHMENT, $encryptionKey, $mineType, $company);
        $fileVersion
            ->setOriginalName($this->fileSystemHelper->normalizeFileName($uploadedFile->getClientOriginalName()))
            ->setSize($uploadedFile->getSize())
        ;
        $file->setCurrentFileVersion($fileVersion);

        $this->fileRepository->save($file);
        $this->messageBus->dispatch(new FileUploaded($file, $context));
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
     * @param User $user
     *
     * @return string

     **@throws InvalidArgumentException
     *
     */
    private function getUserDirectory(User $user): string
    {
        if (null === $user->getId()) {
            throw new InvalidArgumentException('Cannot find the upload destination. The user id is empty.');
        }

        return (string) $user->getId();
    }
}
