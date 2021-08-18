<?php

declare(strict_types=1);

namespace KLS\Core\Service\FileInput;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\Core\DataTransformer\FileInputDataUploadInterface;
use KLS\Core\DTO\FileInput;
use KLS\Core\Entity\File;
use KLS\Core\Entity\Message;
use KLS\Core\Entity\MessageFile;
use KLS\Core\Entity\User;
use KLS\Core\Repository\MessageFileRepository;
use KLS\Core\Repository\MessageRepository;
use KLS\Core\Security\Voter\MessageVoter;
use KLS\Core\Service\File\FileUploadManager;
use League\Flysystem\FilesystemException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class FileInputMessageUploader implements FileInputDataUploadInterface
{
    use FileInputDataUploadTrait;

    private Security $security;
    private FileUploadManager $fileUploadManager;
    private MessageFileRepository $messageFileRepository;
    private MessageRepository $messageRepository;

    public function __construct(
        Security $security,
        FileUploadManager $fileUploadManager,
        MessageFileRepository $messageFileRepository,
        MessageRepository $messageRepository
    ) {
        $this->security              = $security;
        $this->fileUploadManager     = $fileUploadManager;
        $this->messageFileRepository = $messageFileRepository;
        $this->messageRepository     = $messageRepository;
    }

    public function supports($targetEntity): bool
    {
        return $targetEntity instanceof Message;
    }

    /**
     * @param Message $targetEntity
     *
     * @throws EnvironmentIsBrokenException
     * @throws IOException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws FilesystemException
     */
    public function upload($targetEntity, FileInput $fileInput, User $user, ?File $file): File
    {
        if (false === $this->security->isGranted(MessageVoter::ATTRIBUTE_ATTACH_FILE, $targetEntity)) {
            throw new AccessDeniedException();
        }

        if (false === $file instanceof File) {
            $file = new File();
        }

        $this->fileUploadManager->upload($fileInput->uploadedFile, $user, $file, [], $this->getCurrentCompany());

        $messagesToBeAttached = [$targetEntity];

        // If it's a broadcast message, then add messageFile to all broadcast messages
        if ($targetEntity->isBroadcast()) {
            $messagesToBeAttached = $this->messageRepository->findBy(['broadcast' => $targetEntity->getBroadcast()]);
        }

        foreach ($messagesToBeAttached as $messageToAddMessageFile) {
            $messageFile = new MessageFile($file, $messageToAddMessageFile);
            $this->messageFileRepository->persist($messageFile);
        }

        $this->messageFileRepository->flush();

        return $file;
    }
}
