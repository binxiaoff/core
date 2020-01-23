<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\{Clients, MarketSegment, Staff};
use Unilend\Traits\ConstantsAwareTrait;

class StaffVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return $subject instanceof Staff && \in_array($attribute, static::getConstants('ATTRIBUTE_'), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        $submitterStaff = $user->getStaff();

        if (null === $submitterStaff) {
            return false;
        }

        if ($submitterStaff->isAdmin() && $subject->getCompany() === $submitterStaff->getCompany()) {
            return true;
        }

        if ($subject->isAdmin()) {
            return false;
        }

        switch ($attribute) {
            case static::ATTRIBUTE_VIEW:
                return true;
            case static::ATTRIBUTE_CREATE:
                return $this->canCreate($subject, $submitterStaff);
            case static::ATTRIBUTE_EDIT:
                return $this->canEdit($subject, $submitterStaff);
            case static::ATTRIBUTE_DELETE:
                return $this->canDelete($subject, $submitterStaff);
        }

        throw new \LogicException('This code should not be reached');
    }

    /**
     * @param Staff $subject
     * @param Staff $submitterStaff
     *
     * @return bool
     */
    private function canCreate(Staff $subject, Staff $submitterStaff): bool
    {
        $canAdministrate = $submitterStaff->isManager() || $submitterStaff->isAdmin();

        // A manager cannot create a staff with markets other than is own. But we can create a staff without market segment (used for invitation via email)
        return  (empty($subject->getRoles()) || $canAdministrate) && (0 === $subject->getMarketSegments()->count()
            || $subject->getMarketSegments()->forAll(static function ($key, MarketSegment $marketSegment) use ($submitterStaff) {
                return $submitterStaff->getMarketSegments()->contains($marketSegment);
            }));
    }

    /**
     * @param Staff $subject
     * @param Staff $submitterStaff
     *
     * @return bool
     */
    private function canDelete(Staff $subject, Staff $submitterStaff): bool
    {
        if (false === $submitterStaff->isManager() || $subject->getCompany() !== $submitterStaff->getCompany()) {
            return false;
        }

        // A manager cannot delete a user with markets other than is own
        return $subject->getMarketSegments()->forAll(static function ($key, MarketSegment $marketSegment) use ($submitterStaff) {
            return $submitterStaff->getMarketSegments()->contains($marketSegment);
        });
    }

    /**
     * @param Staff $subject
     * @param Staff $submitterStaff
     *
     * @return bool
     */
    private function canEdit(Staff $subject, Staff $submitterStaff): bool
    {
        if (false === $submitterStaff->isManager() || $subject->getCompany() !== $submitterStaff->getCompany()) {
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
}
