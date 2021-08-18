<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Service\FileInput;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\Core\DataTransformer\FileInputDataUploadInterface;
use KLS\Core\DTO\FileInput;
use KLS\Core\Entity\File;
use KLS\Core\Entity\User;
use KLS\Core\Service\File\FileUploadManager;
use KLS\Syndication\Agency\Entity\Term;
use KLS\Syndication\Agency\Security\Voter\TermVoter;
use League\Flysystem\FilesystemException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class FileInputTermUploader implements FileInputDataUploadInterface
{
    private Security $security;
    private FileUploadManager $fileUploadManager;

    public function __construct(Security $security, FileUploadManager $fileUploadManager)
    {
        $this->security          = $security;
        $this->fileUploadManager = $fileUploadManager;
    }

    public function supports($targetEntity): bool
    {
        return $targetEntity instanceof Term;
    }

    /**
     * @param Term $targetEntity
     *
     * @throws EnvironmentIsBrokenException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws FilesystemException
     */
    public function upload($targetEntity, FileInput $fileInput, User $user, ?File $file): File
    {
        if (false === $this->security->isGranted(TermVoter::ATTRIBUTE_EDIT, $targetEntity)) {
            throw new AccessDeniedException();
        }

        $file = new File();

        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, $file, ['termId' => $targetEntity->getId()]);
        $targetEntity->setBorrowerDocument($file);

        return $file;
    }
}
