<?php

declare(strict_types=1);

namespace KLS\Core\Service\File;

use KLS\Core\Entity\FileDownload;
use KLS\Core\Entity\Message;
use KLS\Core\Entity\User;
use KLS\Core\Repository\MessageFileRepository;
use KLS\Core\Security\Voter\MessageVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FileDownloadMessagePermissionChecker implements FileDownloadPermissionCheckerInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private MessageFileRepository $messageFileRepository;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, MessageFileRepository $messageFileRepository)
    {
        $this->authorizationChecker  = $authorizationChecker;
        $this->messageFileRepository = $messageFileRepository;
    }

    public function check(FileDownload $fileDownload, User $user): bool
    {
        if (false === $this->supports($fileDownload)) {
            return false;
        }

        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        $messageFiles = $this->messageFileRepository->findBy(['file' => $fileDownload->getFileVersion()->getFile()]);

        foreach ($messageFiles as $messageFile) {
            if ($this->authorizationChecker->isGranted(MessageVoter::ATTRIBUTE_VIEW, $messageFile->getMessage())) {
                return true;
            }
        }

        return false;
    }

    private function supports(FileDownload $fileDownload): bool
    {
        return Message::FILE_TYPE_MESSAGE_ATTACHMENT === $fileDownload->getType();
    }
}
