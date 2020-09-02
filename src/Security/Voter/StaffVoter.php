<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\PersistentCollection;
use Unilend\Entity\{Clients, MarketSegment, Staff};

class StaffVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW       = 'view';
    public const ATTRIBUTE_EDIT       = 'edit';
    public const ATTRIBUTE_ADMIN_EDIT = 'admin_edit';
    public const ATTRIBUTE_DELETE     = 'delete';
    public const ATTRIBUTE_CREATE     = 'create';


    /**
     * @param mixed   $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function fulfillPreconditions($subject, Clients $user): bool
    {
        return (bool) $user->getCurrentStaff();
    }

    /**
     * @param Staff   $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function isGrantedAll($subject, Clients $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        return $submitterStaff && $submitterStaff->isAdmin() && $subject->getCompany() === $submitterStaff->getCompany();
    }

    /**
     * @param Staff   $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function canCreate(Staff $subject, Clients $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        return
            // You can only create a staff for a the connected company or is the company is not par of Crédit Agricole
            (false === $subject->getCompany()->isCAGMember() || ($submitterStaff && $submitterStaff->getCompany() === $subject->getCompany() && $submitterStaff->isAdmin())) &&
            // You must be connected with a crédit agricole group bank
            $submitterStaff->getCompany()->isCAGMember() &&
            // An admin cannot create a staff with markets other than is own. But we can create a staff without market segment (used for invitation via email)
            (0 === $subject->getMarketSegments()->count()
            || $subject->getMarketSegments()->forAll(static function ($key, MarketSegment $marketSegment) use ($submitterStaff) {
                return $submitterStaff->getMarketSegments()->contains($marketSegment);
            }));
    }

    /**
     * @param Staff   $staff
     * @param Clients $user
     *
     * @return bool
     */
    protected function canAdminEdit(Staff $staff, Clients $user): bool
    {
        // or is admin, already in isGrantedAll()
        return $user->getCurrentStaff() && $user->getCurrentStaff()->isManager() && $staff->getCompany() === $user->getCurrentStaff()->getCompany();
    }

    /**
     * @param Staff   $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function canDelete(Staff $subject, Clients $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        return $submitterStaff && $submitterStaff->isAdmin() && $submitterStaff->getCompany() === $subject->getCompany();
    }

    /**
     * @param Staff   $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function canEdit(Staff $subject, Clients $user): bool
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
     * @param Staff   $employee
     * @param Clients $manager
     *
     * @return bool
     */
    private function ableToManage(Staff $employee, Clients $manager): bool
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
