<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\{Clients, Project};
use Unilend\Traits\ConstantsAwareTrait;

class ProjectVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_VIEW         = 'view';
    public const ATTRIBUTE_EDIT         = 'edit';
    public const ATTRIBUTE_MANAGER_BIDS = 'manage_bids';
    public const ATTRIBUTE_SCORE        = 'score';
    public const ATTRIBUTE_BID          = 'bid';
    public const ATTRIBUTE_COMMENT      = 'comment';

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        $attributes = self::getConstants('ATTRIBUTE_');

        if (false === in_array($attribute, $attributes)) {
            return false;
        }

        if (false === $subject instanceof Project) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $project, TokenInterface $token): bool
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        switch ($attribute) {
            case self::ATTRIBUTE_VIEW:
                return $this->canView($project, $user);
            case self::ATTRIBUTE_EDIT:
                return $this->canEdit($project, $user);
            case self::ATTRIBUTE_MANAGER_BIDS:
                return $this->canManageBids($project, $user);
            case self::ATTRIBUTE_SCORE:
                return $this->canScore($project, $user);
            case self::ATTRIBUTE_BID:
                return $this->canBid($project, $user);
            case self::ATTRIBUTE_COMMENT:
                return $this->canComment($project, $user);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @return bool
     */
    private function canView(Project $project, Clients $user): bool
    {
        if ($this->canEdit($project, $user) || $this->canBid($project, $user)) {
            return true;
        }

        return null !== $project->getProjectParticipantByCompany($user->getCompany());
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @return bool
     */
    private function canEdit(Project $project, Clients $user): bool
    {
        if ($this->canManageBids($project, $user)) {
            return true;
        }

        return in_array($user->getCompany(), [
            $project->getArranger()->getCompany(),
            $project->getRun()->getCompany(),
            $project->getSubmitterCompany(),
        ]);
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @return bool
     */
    private function canManageBids(Project $project, Clients $user): bool
    {
        return $user->getCompany() === $project->getArranger()->getCompany();
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @return bool
     */
    private function canScore(Project $project, Clients $user): bool
    {
        return $user->getCompany() === $project->getRun()->getCompany();
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @return bool
     */
    private function canBid(Project $project, Clients $user): bool
    {
        return in_array($user->getCompany(), $project->getLenderCompanies());
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @return bool
     */
    private function canComment(Project $project, Clients $user): bool
    {
        return $this->canView($project, $user);
    }
}
