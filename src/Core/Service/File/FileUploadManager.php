<?php

declare(strict_types=1);

namespace KLS\Core\Service\File;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use InvalidArgumentException;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\File;
use KLS\Core\Entity\FileVersion;
use KLS\Core\Entity\User;
use KLS\Core\Message\File\FileUploaded;
use KLS\Core\Repository\FileRepository;
use KLS\Core\Service\FileSystem\FileSystemHelper;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

class FileUploadManager
{
    private FileSystemHelper $fileSystemHelper;
    private FilesystemOperator $userAttachmentFilesystem;
    private FileRepository $fileRepository;
    private MessageBusInterface $messageBus;

    public function __construct(
        FileSystemHelper $fileSystemHelper,
        FilesystemOperator $userAttachmentFilesystem,
        FileRepository $fileRepository,
        MessageBusInterface $messageBus
    ) {
        $this->fileSystemHelper         = $fileSystemHelper;
        $this->userAttachmentFilesystem = $userAttachmentFilesystem;
        $this->fileRepository           = $fileRepository;
        $this->messageBus               = $messageBus;
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws FilesystemException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function upload(
        UploadedFile $uploadedFile,
        User $uploader,
        File $file,
        array $context = [],
        ?Company $company = null
    ): void {
        $mineType                               = $uploadedFile->getMimeType();
        [$relativeUploadedPath, $encryptionKey] = $this->uploadFile(
            $uploadedFile,
            $this->userAttachmentFilesystem,
            $this->getUserDirectory($uploader)
        );

        $fileVersion = new FileVersion(
            $relativeUploadedPath,
            $uploader,
            $file,
            FileVersion::FILE_SYSTEM_USER_ATTACHMENT,
            $encryptionKey,
            $mineType,
            $company
        );
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
    private function uploadFile(UploadedFile $file, FilesystemOperator $filesystem, string $subdirectory): array
    {
        $uploadDirectory = $this->fileSystemHelper->normalizeDirectory(DIRECTORY_SEPARATOR, $subdirectory);

        $filename = $this->generateFileName($file, $filesystem, $uploadDirectory);
        $filePath = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

        $key = $this->fileSystemHelper->writeTempFileToFileSystem($file->getPathname(), $filesystem, $filePath, true);

        return [$filePath, $key];
    }

    /**
     * @throws FilesystemException
     */
    private function generateFileName(
        UploadedFile $uploadedFile,
        FilesystemOperator $filesystem,
        string $uploadDirectory
    ): string {
        $originalFilename = $this->fileSystemHelper->normalizeFileName(
            \pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME)
        );
        $fileNameWithExtension = $originalFilename . '-' . \uniqid('', true) . '.' .
            $uploadedFile->guessExtension() ?? $uploadedFile->getClientOriginalExtension();

        if ($filesystem->fileExists($uploadDirectory . DIRECTORY_SEPARATOR . $fileNameWithExtension)) {
            $fileNameWithExtension = $this->generateFileName($uploadedFile, $filesystem, $uploadDirectory);
        }

        return $fileNameWithExtension;
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
