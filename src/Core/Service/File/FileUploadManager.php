<?php

declare(strict_types=1);

namespace Unilend\Core\Service\File;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\File;
use Unilend\Core\Entity\FileVersion;
use Unilend\Core\Entity\User;
use Unilend\Core\Message\File\FileUploaded;
use Unilend\Core\Repository\FileRepository;
use Unilend\Core\Service\FileSystem\FileSystemHelper;

class FileUploadManager
{
    private FileSystemHelper $fileSystemHelper;
    private FilesystemOperator $userAttachmentFilesystem;
    private FileRepository $fileRepository;
    private MessageBusInterface $messageBus;

    public function __construct(FileSystemHelper $fileSystemHelper, FilesystemOperator $userAttachmentFilesystem, FileRepository $fileRepository, MessageBusInterface $messageBus)
    {
        $this->fileSystemHelper         = $fileSystemHelper;
        $this->userAttachmentFilesystem = $userAttachmentFilesystem;
        $this->fileRepository           = $fileRepository;
        $this->messageBus               = $messageBus;
    }

    /**
     * @param File|null $file
     *
     * @throws EnvironmentIsBrokenException
     * @throws FilesystemException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function upload(UploadedFile $uploadedFile, User $uploader, File $file, array $context = [], ?Company $company = null): void
    {
        $mineType                               = $uploadedFile->getMimeType();
        [$relativeUploadedPath, $encryptionKey] = $this->uploadFile($uploadedFile, $this->userAttachmentFilesystem, $this->getUserDirectory($uploader));

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
     * @throws EnvironmentIsBrokenException
     * @throws FilesystemException
     * @throws IOException
     */
    private function uploadFile(UploadedFile $file, FilesystemOperator $filesystem, ?string $subdirectory = null, string $uploadRootDirectory = '/', bool $encryption = true): array
    {
        $hash         = \hash('sha256', $subdirectory ?? \uniqid('', true));
        $subdirectory = $hash[0] . DIRECTORY_SEPARATOR . $hash[1] . ($subdirectory ? DIRECTORY_SEPARATOR . $subdirectory : '');

        $uploadRootDirectory = $this->normalizePath($uploadRootDirectory);
        $uploadDirectory     = $uploadRootDirectory . DIRECTORY_SEPARATOR . $subdirectory;

        $filename = $this->generateFileName($file, $filesystem, $uploadDirectory);
        $filePath = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

        $key = $this->fileSystemHelper->writeTempFileToFileSystem($file->getPathname(), $filesystem, $filePath, $encryption);

        return [$filePath, $key];
    }

    /**
     * @throws FilesystemException
     */
    private function generateFileName(UploadedFile $uploadedFile, FilesystemOperator $filesystem, string $uploadDirectory): string
    {
        $originalFilename      = $this->fileSystemHelper->normalizeFileName(\pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));
        $fileNameWithExtension = $originalFilename . '-' . \uniqid('', true) . '.' . $uploadedFile->guessExtension() ?? $uploadedFile->getClientOriginalExtension();

        if ($filesystem->fileExists($uploadDirectory . DIRECTORY_SEPARATOR . $fileNameWithExtension)) {
            $fileNameWithExtension = $this->generateFileName($uploadedFile, $filesystem, $uploadDirectory);
        }

        return $fileNameWithExtension;
    }

    private function normalizePath(string $path): string
    {
        return DIRECTORY_SEPARATOR === \mb_substr($path, -1) ? \mb_substr($path, 0, -1) : $path;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getUserDirectory(User $user): string
    {
        if (null === $user->getId()) {
            throw new InvalidArgumentException('Cannot find the upload destination. The user id is empty.');
        }

        return (string) $user->getId();
    }
}
