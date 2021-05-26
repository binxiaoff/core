<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Exception;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class ProjectVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_DELETE = 'delete';

    /**
     * @throws Exception
     */
    protected function canView(Project $project, User $user): bool
    {
        foreach (ProjectRoleVoter::getAvailableRoles() as $role) {
            if ($this->authorizationChecker->isGranted($role, $project)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Do not use can{Role} because object is not yet in database.
     */
    protected function canCreate(Project $project, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        // Est-ce que l'on devrai vérifier l'héritage pour la création des projets ? .
        return $staff->getCompany() === $project->getAgentCompany() && $staff->hasAgencyProjectCreationPermission();
    }

    protected function canEdit(Project $project, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $project)
            && true === $project->isEditable();
    }

    protected function canDelete(Project $project, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $project)
            && true === $project->isEditable();
    }
}
