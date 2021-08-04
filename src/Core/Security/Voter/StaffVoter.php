<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Entity\{Staff};
use Unilend\Core\Repository\CompanyAdminRepository;

class StaffVoter extends AbstractEntityVoter
{
    private CompanyAdminRepository $companyAdminRepository;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        CompanyAdminRepository $companyAdminRepository
    ) {
        parent::__construct($authorizationChecker);
        $this->companyAdminRepository = $companyAdminRepository;
    }

    /**
     * @param Staff $subject
     */
    protected function fulfillPreconditions($subject, User $user): bool
    {
        $currentStaff = $user->getCurrentStaff();

        if (false === ($currentStaff instanceof Staff)) {
            return false;
        }

        return false === $currentStaff->isArchived() && false === $subject->isArchived();
    }

    /**
     * @param Staff $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        $company = $subject->getCompany();

        return null !== $this->companyAdminRepository->findOneBy(['company' => $company, 'user' => $user]);
    }

    protected function canCreate(Staff $staff, User $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        if (false === $submitterStaff instanceof Staff) {
            return false;
        }

        return
            (
                // You can create a staff for external banks
                false === $staff->getCompany()->isCAGMember()
                || (
                    $submitterStaff->getCompany() === $staff->getCompany()
                    && $submitterStaff->isManager()
                )
            )
            // You must be connected with a crédit agricole group bank
            && $submitterStaff->getCompany()->isCAGMember();
    }

    protected function canView(Staff $staff, User $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        if (null === $submitterStaff) {
            return false;
        }

        return $staff === $submitterStaff || $this->isSuperior($submitterStaff, $staff);
    }

    protected function canEdit(Staff $staff, User $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        if (null === $submitterStaff) {
            return false;
        }

        if (false === $submitterStaff->isManager()) {
            return false;
        }

        // A staff cannot edit self
        if ($submitterStaff->getPublicId() === $staff->getPublicId()) {
            return false;
        }

        return $this->isSuperior($submitterStaff, $staff);
    }

    private function isSuperior(Staff $superior, Staff $subordinate): bool
    {
        if (false === $superior->isManager()) {
            return false;
        }

        return \in_array($superior->getTeam(), $subordinate->getTeam()->getAncestors(), true) || $superior->getTeam() === $subordinate->getTeam();
    }
}
