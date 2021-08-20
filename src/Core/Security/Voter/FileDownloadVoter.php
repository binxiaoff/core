<?php

declare(strict_types=1);

namespace KLS\Core\Security\Voter;

use KLS\Core\Entity\FileDownload;
use KLS\Core\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FileDownloadVoter extends AbstractEntityVoter
{
    /** @var iterable|FileDownloadVoterInterface[] */
    private iterable $voters;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, iterable $voters)
    {
        parent::__construct($authorizationChecker);
        $this->voters = $voters;
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
        foreach ($this->voters as $voter) {
            if ($voter->supports($fileDownload) && false === $voter->canCreate($fileDownload, $user)) {
                return false;
            }
        }

        return true;
    }
}
