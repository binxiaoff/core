<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Exception;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\Clients;
use Unilend\Entity\ProjectMessage;
use Unilend\Traits\ConstantsAwareTrait;

class ProjectMessageVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return $subject instanceof ProjectMessage && \in_array($attribute, self::getConstants('ATTRIBUTE_'), true);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients || null === $user->getCompany()) {
            return false;
        }

        switch ($attribute) {
            case self::ATTRIBUTE_CREATE:
                return $this->canCreate($subject, $user);
            case self::ATTRIBUTE_EDIT:
                return $this->canEdit($subject, $user);
            case self::ATTRIBUTE_DELETE:
                return $this->canDelete($subject, $user);
        }

        throw new LogicException('This code should not be reached');
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
