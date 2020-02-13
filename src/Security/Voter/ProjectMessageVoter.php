<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Exception;
use Unilend\Entity\{Clients, ProjectMessage};

class ProjectMessageVoter extends AbstractVoter
{
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return $subject instanceof ProjectMessage && parent::supports($attribute, $subject);
    }

    /**
     * @param ProjectMessage $subject
     * @param Clients        $user
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function canCreate(ProjectMessage $subject, Clients $user): bool
    {
        /** @var ProjectMessage $subject */
        $arranger           = $subject->getParticipation()->getProject()->getArranger();
        $arrangerCompany    = $arranger ? $arranger->getCompany() : null;
        $participantCompany = $subject->getParticipation()->getCompany();

        return $user->getCompany() === $arrangerCompany || $user->getCompany() === $participantCompany;
    }

    /**
     * @param ProjectMessage $subject
     * @param Clients        $user
     *
     * @return bool
     */
    protected function canEdit(ProjectMessage $subject, Clients $user): bool
    {
        return $user === $subject->getAddedBy();
    }

    /**
     * @param ProjectMessage $subject
     * @param Clients        $user
     *
     * @return bool
     */
    protected function canDelete(ProjectMessage $subject, Clients $user): bool
    {
        return $user === $subject->getAddedBy();
    }
}
