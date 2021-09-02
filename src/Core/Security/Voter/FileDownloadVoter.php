<?php

declare(strict_types=1);

namespace KLS\Core\Security\Voter;

use KLS\Core\Entity\FileDownload;
use KLS\Core\Entity\User;
use KLS\Core\Service\File\FileDownloadPermissionCheckerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FileDownloadVoter extends AbstractEntityVoter
{
    /** @var iterable|FileDownloadPermissionCheckerInterface[] */
    private iterable $permissionCheckers;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, iterable $permissionCheckers)
    {
        parent::__construct($authorizationChecker);
        $this->permissionCheckers = $permissionCheckers;
    }

    /**
     * @param FileDownload $subject
     */
    protected function fulfillPreconditions($subject, User $user): bool
    {
        return $subject->getFileVersion()->getFile() && $subject->getFileVersion() === $subject->getFileVersion()->getFile()->getCurrentFileVersion();
    }

    protected function canCreate(FileDownload $fileDownload, User $user): bool
    {
        foreach ($this->permissionCheckers as $permissionChecker) {
            if ($permissionChecker->check($fileDownload, $user)) {
                return true;
            }
        }

        return false;
    }
}
