<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Security\Voter;

use KLS\Core\Entity\Staff;
use KLS\Core\Traits\ConstantsAwareTrait;
use KLS\CreditGuaranty\Entity\Program;
use KLS\CreditGuaranty\Service\StaffPermissionManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProgramRoleVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ROLE_MANAGER     = 'manager';
    public const ROLE_PARTICIPANT = 'participant';

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
        return $subject instanceof Program && \in_array($attribute, static::getAvailableRoles(), true);
    }

    /**
     * @param Program $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $staff = $token->hasAttribute('staff') ? $token->getAttribute('staff') : null;

        if (false === $staff instanceof Staff) {
            return false;
        }

        switch ($attribute) {
            case self::ROLE_MANAGER:
                return $this->isManager($subject, $staff);

            case self::ROLE_PARTICIPANT:
                return $this->isParticipant($subject, $staff);

            default:
                throw new \LogicException('This code should never be reached');
        }
    }

    private function isManager(Program $program, Staff $staff): bool
    {
        return $staff->getCompany() === $program->getManagingCompany() && $this->staffPermissionManager->checkCompanyGroupTag($program, $staff);
    }

    private function isParticipant(Program $program, Staff $staff): bool
    {
        return $program->hasParticipant($staff->getCompany()) && $this->staffPermissionManager->checkCompanyGroupTag($program, $staff);
    }
}
