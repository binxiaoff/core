<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramStatus;

class ProgramVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';

    protected function canCreate(Program $program, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && Company::SHORT_CODE_CASA === $staff->getCompany()->getShortCode()
            && ($staff->isAdmin() || in_array($program->getCompanyGroupTag(), $staff->getCompanyGroupTags(), true));
    }

    protected function canView(Program $program, User $user): bool
    {
        // todo: To be completed
        return true;
    }

    protected function canDelete(Program $program, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->canEdit($program, $staff->getUser())
            && ProgramStatus::STATUS_DRAFT === $program->getCurrentStatus()->getStatus();
    }

    protected function canEdit(Program $program, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && Company::SHORT_CODE_CASA === $staff->getCompany()->getShortCode()
            && ($program->isInDraft() || $program->isPaused())
            && ($staff->isAdmin() || in_array($program->getCompanyGroupTag(), $staff->getCompanyGroupTags(), true) || $program->getAddedBy() === $staff);
    }
}
