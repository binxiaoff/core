<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\PersistentCollection;
use Unilend\Entity\{Clients, MarketSegment, Staff};

class StaffVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param mixed   $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function fulfillPreconditions($subject, Clients $user): bool
    {
        $submitterStaff = $user->getStaff();

        return $submitterStaff ? true : false;
    }

    /**
     * @param Staff   $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function isGrantedAll($subject, Clients $user): bool
    {
        $submitterStaff = $user->getStaff();

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
        $submitterStaff = $user->getStaff();

        // A manager cannot create a staff with markets other than is own. But we can create a staff without market segment (used for invitation via email)
        return $this->ableToManage($subject, $user) && (0 === $subject->getMarketSegments()->count()
            || $subject->getMarketSegments()->forAll(static function ($key, MarketSegment $marketSegment) use ($submitterStaff) {
                return $submitterStaff->getMarketSegments()->contains($marketSegment);
            }));
    }

    /**
     * @param Staff   $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function canDelete(Staff $subject, Clients $user): bool
    {
        $submitterStaff = $user->getStaff();

        // A manager cannot delete a user with markets other than is own
        return $this->ableToManage($subject, $user) && $subject->getMarketSegments()->forAll(static function ($key, MarketSegment $marketSegment) use ($submitterStaff) {
            return $submitterStaff->getMarketSegments()->contains($marketSegment);
        });
    }

    /**
     * @param Staff   $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function canEdit(Staff $subject, Clients $user): bool
    {
        $submitterStaff = $user->getStaff();
        if (false === $this->ableToManage($subject, $user)) {
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
     * @param Staff   $managee
     * @param Clients $manager
     *
     * @return bool
     */
    private function ableToManage(Staff $managee, Clients $manager): bool
    {
        $managerStaff = $manager->getStaff();

        if (null === $managerStaff) {
            return false;
        }

        $managerCompany = $managerStaff->getCompany();

        return ($managee->getCompany() === $managerCompany && false === $managee->isAdmin() && $managerStaff !== $managee)
            || ($managee->getCompany() === $managerCompany && $managerStaff->isAdmin());
    }
}
