<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service\FileInput;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\Core\DataTransformer\FileInputDataUploadInterface;
use KLS\Core\DTO\FileInput;
use KLS\Core\Entity\File;
use KLS\Core\Entity\User;
use KLS\Core\Exception\File\DenyUploadExistingFileException;
use KLS\Core\Service\File\FileUploadManager;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Security\Voter\ProjectVoter;
use League\Flysystem\FilesystemException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class FileInputProjectParticipationUploader implements FileInputDataUploadInterface
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
        return $targetEntity instanceof ProjectParticipation;
    }

    /**
     * @param ProjectParticipation $targetEntity
     *
     * @throws EnvironmentIsBrokenException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws FilesystemException
     */
    public function upload($targetEntity, FileInput $fileInput, User $user, ?File $file): File
    {
        if (false === $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $targetEntity->getProject())) {
            throw new AccessDeniedException();
        }

        $isPublished = $targetEntity->getProject()->isPublished();
        $existingNda = $targetEntity->getNda();

        if ($isPublished && null !== $file && null !== $existingNda && $file !== $existingNda) {
            throw new DenyUploadExistingFileException($fileInput, $existingNda, $targetEntity);
        }

        $file           = $isPublished && $existingNda ? $existingNda : new File();
        $token          = $this->security->getToken();
        $currentCompany = $token && $token->hasAttribute('company') ? $token->getAttribute('company') : null;

        $this->fileUploadManager->upload(
            $fileInput->uploadedFile,
            $user,
            $file,
            ['projectParticipationId' => $targetEntity->getId()],
            $currentCompany
        );

        $targetEntity->setNda($file);

        return $file;
    }
}
