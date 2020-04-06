<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\Clients;
use Unilend\Entity\ProjectFile;

class ProjectFileVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_EDIT   = 'edit';

    /**
     * @param ProjectFile $projectFile
     * @param Clients     $user
     *
     * @return bool
     */
    protected function fulfillPreconditions($projectFile, Clients $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $projectFile->getProject());
    }
}
