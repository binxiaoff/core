<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\Clients;
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
        return $subject instanceof Staff && in_array($attribute, static::getConstants('ATTRIBUTE_'), true);
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

        return $submitterStaff
            && $subject->getCompany() === $submitterStaff->getCompany()
            && array_sum(array_map([$submitterStaff, 'hasRole'], [Staff::DUTY_STAFF_ADMIN, Staff::DUTY_STAFF_MANAGER]));
    }
}
