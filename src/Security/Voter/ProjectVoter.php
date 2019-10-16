<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Exception;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\{Clients, Embeddable\Permission, Project, ProjectStatus};
use Unilend\Repository\ProjectParticipationRepository;
use Unilend\Traits\ConstantsAwareTrait;

class ProjectVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_PREVIEW     = 'preview';
    public const ATTRIBUTE_VIEW        = 'view';
    public const ATTRIBUTE_EDIT        = 'edit';
    public const ATTRIBUTE_MANAGE_BIDS = 'manage_bids';
    public const ATTRIBUTE_RATE        = 'rate';
    public const ATTRIBUTE_BID         = 'bid';
    public const ATTRIBUTE_COMMENT     = 'comment';

    /** @var ProjectParticipationRepository */
    private $projectParticipationRepository;

    /**
     * @param ProjectParticipationRepository $projectParticipation
     */
    public function __construct(ProjectParticipationRepository $projectParticipation)
    {
        $this->projectParticipationRepository = $projectParticipation;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        $attributes = self::getConstants('ATTRIBUTE_');

        if (false === in_array($attribute, $attributes, true)) {
            return false;
        }

        if (false === $subject instanceof Project) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function voteOnAttribute($attribute, $project, TokenInterface $token): bool
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        switch ($attribute) {
            case self::ATTRIBUTE_PREVIEW:
                return $this->canPreview($project, $user);
            case self::ATTRIBUTE_VIEW:
                return $this->canView($project, $user);
            case self::ATTRIBUTE_EDIT:
                return $this->canEdit($project, $user);
            case self::ATTRIBUTE_MANAGE_BIDS:
                return $this->canManageBids($project, $user);
            case self::ATTRIBUTE_RATE:
                return $this->canRate($project, $user);
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
     * @throws Exception
     *
     * @return bool
     */
    private function canPreview(Project $project, Clients $user): bool
    {
        if ($this->canEdit($project, $user)) {
            return true;
        }

        if ($project->getCurrentStatus()->getStatus() < ProjectStatus::STATUS_PUBLISHED) {
            return false;
        }

        return null !== $project->getProjectParticipationByCompany($user->getCompany());
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    private function canView(Project $project, Clients $user): bool
    {
        return $this->canPreview($project, $user) && $project->checkUserConfidentiality($user);
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    private function canEdit(Project $project, Clients $user): bool
    {
        return
            0 < count($project->getSubmitterCompany()->getStaff($user))
            || (
                ($participation = $this->projectParticipationRepository->findByProjectAndClient($project, $user))
                && $participation->getPermission()->has(Permission::PERMISSION_EDIT)
            );
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    private function canManageBids(Project $project, Clients $user): bool
    {
        return $project->getArranger() && $user->getCompany() === $project->getArranger()->getCompany();
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    private function canRate(Project $project, Clients $user): bool
    {
        return $project->getRun() && $user->getCompany() === $project->getRun()->getCompany();
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    private function canBid(Project $project, Clients $user): bool
    {
        return
            $this->canView($project, $user)
            && in_array($user->getCompany(), $project->getLenderCompanies()->toArray(), true)
        ;
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    private function canComment(Project $project, Clients $user): bool
    {
        return $this->canView($project, $user);
    }
}
