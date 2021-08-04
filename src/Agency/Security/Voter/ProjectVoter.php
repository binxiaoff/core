<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Exception;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\CompanyModule;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class ProjectVoter extends AbstractEntityVoter
{
    /**
     * Do not use can{Role} because object is not yet in database.
     */
    protected function canCreate(Project $project, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        // Should we verify permission inheritance for agency project creation .
        return $staff->getCompany() === $project->getAgentCompany()
            && $staff->hasAgencyProjectCreationPermission()
            && $staff->getCompany()->hasModuleActivated(CompanyModule::MODULE_AGENCY);
    }

    /**
     * @throws Exception
     */
    protected function canView(Project $project, User $user): bool
    {
        // It might be interesting to copy this code in place of $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $project);.
        foreach (ProjectRoleVoter::getAvailableRoles() as $role) {
            if ($this->authorizationChecker->isGranted($role, $project)) {
                return true;
            }
        }

        return false;
    }

    protected function canEdit(Project $project, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $project)
            && $project->isEditable();
    }

    protected function canDelete(Project $project, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $project)
            && $project->isEditable();
    }
}
