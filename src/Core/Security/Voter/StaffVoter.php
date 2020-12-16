<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Doctrine\ORM\PersistentCollection;
use Unilend\Core\Entity\User;
use Unilend\Core\Entity\{Staff};

class StaffVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW       = 'view';
    public const ATTRIBUTE_EDIT       = 'edit';
    public const ATTRIBUTE_ADMIN_EDIT = 'admin_edit';
    public const ATTRIBUTE_DELETE     = 'delete';
    public const ATTRIBUTE_CREATE     = 'create';


    /**
     * @param mixed $subject
     * @param User  $user
     *
     * @return bool
     */
    protected function fulfillPreconditions($subject, User $user): bool
    {
        return (bool) $user->getCurrentStaff();
    }

    /**
     * @param Staff $subject
     * @param User  $user
     *
     * @return bool
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        return $submitterStaff && $submitterStaff->isAdmin() && $subject->getCompany() === $submitterStaff->getCompany();
    }

    /**
     * @param Staff $subject
     * @param User  $user
     *
     * @return bool
     */
    protected function canCreate(Staff $subject, User $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        return
            (
                // You can create a staff for external banks
                false === $subject->getCompany()->isCAGMember()
                || (
                    // Or You can, as an admin, create a staff; or as a manager, create a non-admin staff for your own bank
                    $submitterStaff
                    && $submitterStaff->getCompany() === $subject->getCompany()
                    && ($submitterStaff->isAdmin() || ($submitterStaff->isManager() && false === $subject->isAdmin()))
                )
            )
            // You must be connected with a crédit agricole group bank
            && $submitterStaff->getCompany()->isCAGMember();
    }

    /**
     * @param Staff $staff
     * @param User  $user
     *
     * @return bool
     */
    protected function canAdminEdit(Staff $staff, User $user): bool
    {
        // or is admin, already in isGrantedAll()
        return $user->getCurrentStaff() && $user->getCurrentStaff()->isManager() && $staff->getCompany() === $user->getCurrentStaff()->getCompany();
    }

    /**
     * @param Staff $subject
     * @param User  $user
     *
     * @return bool
     */
    protected function canDelete(Staff $subject, User $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        return $submitterStaff && $submitterStaff->isAdmin() && $submitterStaff->getCompany() === $subject->getCompany();
    }

    /**
     * @param Staff $subject
     * @param User  $user
     *
     * @return bool
     */
    protected function canEdit(Staff $subject, User $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        if (null === $submitterStaff) {
            return false;
        }

        if (false === $this->ableToManage($subject, $user)) {
            return false;
        }

        // A manager cannot archive a staff or modify an archived staff
        if ($subject->isArchived()) {
            return false;
        }

        /** @var PersistentCollection $subjectMarketSegments */
        $subjectMarketSegments = $subject->getMarketSegments();

        // TODO see if there is better way to get this
        if (false === $subjectMarketSegments instanceof PersistentCollection) {
            return false;
        }

        $previous = $subject->getMarketSegments()->getSnapshot();

        // A manager cannot add markets segment than is own
        foreach ($subjectMarketSegments as $marketSegment) {
            if (false === ($submitterStaff->getMarketSegments()->contains($marketSegment) || \in_array($marketSegment, $previous, true))) {
                return false;
            }
        }

        // A manager cannot delete a market segment other than is own
        foreach ($previous as $marketSegment) {
            if (false === ($subjectMarketSegments->contains($marketSegment) || $submitterStaff->getMarketSegments()->contains($marketSegment))) {
                return false;
            }
        }

        return true;
    }

    /**
     * TODO It might be interessing to return this data to the front.
     *
     * @param Staff $employee
     * @param User  $manager
     *
     * @return bool
     */
    private function ableToManage(Staff $employee, User $manager): bool
    {
        $managerStaff = $manager->getCurrentStaff();

        if (null === $managerStaff) {
            return false;
        }

        $managerCompany = $managerStaff->getCompany();

        return ($employee->getCompany() === $managerCompany && false === $employee->isAdmin() && $managerStaff !== $employee && $managerStaff->isManager())
            || ($employee->getCompany() === $managerCompany && $managerStaff->isAdmin());
    }
}
