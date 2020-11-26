<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Core\Entity\Clients;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Entity\ProjectFile;

class ProjectFileVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';

    /**
     * @param ProjectFile $projectFile
     * @param Clients     $user
     *
     * @return bool
     */
    protected function isGrantedAll($projectFile, Clients $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $projectFile->getProject());
    }
}
