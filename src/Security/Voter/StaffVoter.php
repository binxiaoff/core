<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\Clients;
use Unilend\Entity\MarketSegment;
use Unilend\Entity\Staff;
use Unilend\Traits\ConstantsAwareTrait;

class StaffVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed  $subject   The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {
        return $subject instanceof Staff && \in_array($attribute, static::getConstants('ATTRIBUTE_'), true);
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string         $attribute
     * @param Staff          $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        $submitterStaff = $user->getStaff();

        if (null === $submitterStaff || $subject->getCompany() !== $submitterStaff->getCompany()) {
            return false;
        }

        if ($submitterStaff->isAdmin()) {
            return true;
        }

        if ($subject->isAdmin() || false === $submitterStaff->isManager()) {
            return false;
        }

        switch ($attribute) {
            case static::ATTRIBUTE_VIEW:
                return true;
            case static::ATTRIBUTE_CREATE:
                return $this->canManagerCreate($subject, $submitterStaff);
            case static::ATTRIBUTE_EDIT:
                return $this->canManagerEdit($subject, $submitterStaff);
            case static::ATTRIBUTE_DELETE:
                return $this->canManagerDelete($subject, $submitterStaff);
        }

        throw new \LogicException('This code should not be reached');
    }

    /**
     * @param Staff $subject
     * @param Staff $submitterStaff
     *
     * @return bool
     */
    private function canManagerCreate(Staff $subject, Staff $submitterStaff)
    {
        // A manager cannot create a user with markets other than is own
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
    private function canManagerDelete(Staff $subject, Staff $submitterStaff)
    {
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
    private function canManagerEdit(Staff $subject, Staff $submitterStaff)
    {
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
