<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Security\Voter;

use KLS\Core\Entity\Staff;
use KLS\Core\Traits\ConstantsAwareTrait;
use KLS\CreditGuaranty\Entity\Reservation;
use KLS\CreditGuaranty\Service\StaffPermissionManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ReservationRoleVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ROLE_MANAGER = 'manager';

    private StaffPermissionManager $staffPermissionManager;

    public function __construct(StaffPermissionManager $staffPermissionManager)
    {
        $this->staffPermissionManager = $staffPermissionManager;
    }

    public static function getAvailableRoles(): array
    {
        return static::getConstants('ROLE_');
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof Reservation && \in_array($attribute, static::getAvailableRoles(), true);
    }

    /**
     * @param Reservation $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $staff = $token->hasAttribute('staff') ? $token->getAttribute('staff') : null;

        if (false === $staff instanceof Staff) {
            return false;
        }

        if (self::ROLE_MANAGER === $attribute) {
            return $this->isManager($subject, $staff);
        }

        throw new \LogicException('This code should never be reached');
    }

    private function isManager(Reservation $reservation, Staff $staff): bool
    {
        return $staff->getCompany() === $reservation->getManagingCompany() && $this->staffPermissionManager->checkCompanyGroupTag($reservation->getProgram(), $staff);
    }
}
