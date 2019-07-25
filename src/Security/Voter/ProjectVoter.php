<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Exception;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\{Clients, Project, ProjectStatusHistory};
use Unilend\Repository\ProjectConfidentialityAcceptanceRepository;
use Unilend\Traits\ConstantsAwareTrait;

class ProjectVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_VIEW              = 'view';
    public const ATTRIBUTE_VIEW_CONFIDENTIAL = 'view_confidential';
    public const ATTRIBUTE_EDIT              = 'edit';
    public const ATTRIBUTE_MANAGE_BIDS       = 'manage_bids';
    public const ATTRIBUTE_RATE              = 'rate';
    public const ATTRIBUTE_BID               = 'bid';
    public const ATTRIBUTE_COMMENT           = 'comment';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;
    /** @var ProjectConfidentialityAcceptanceRepository */
    private $acceptanceRepository;

    /**
     * @param AuthorizationCheckerInterface              $authorizationChecker
     * @param ProjectConfidentialityAcceptanceRepository $acceptanceRepository
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ProjectConfidentialityAcceptanceRepository $acceptanceRepository)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->acceptanceRepository = $acceptanceRepository;
    }

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
            case self::ATTRIBUTE_VIEW_CONFIDENTIAL:
                return $this->canViewConfidential($project, $user);
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
    private function canView(Project $project, Clients $user): bool
    {
        if ($this->canEdit($project, $user)) {
            return true;
        }

        if ($project->getCurrentProjectStatusHistory()->getStatus() < ProjectStatusHistory::STATUS_PUBLISHED) {
            return false;
        }

        return null !== $project->getProjectParticipantByCompany($user->getCompany());
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    private function canViewConfidential(Project $project, Clients $user): bool
    {
        if (false === $this->canView($project, $user)) {
            return false;
        }

        if (false === $project->isConfidential()) {
            return true;
        }

        if ($user->getCompany() === $project->getSubmitterCompany()) {
            return true;
        }

        $acceptance = $this->acceptanceRepository->findOneBy(['project' => $project, 'client' => $user]);

        return null !== $acceptance;
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
        if ($this->canManageBids($project, $user)) {
            return true;
        }

        return in_array($user->getCompany(), [
            $project->getArranger() ? $project->getArranger()->getCompany() : null,
            $project->getDeputyArranger() ? $project->getDeputyArranger()->getCompany() : null,
            $project->getRun() ? $project->getRun()->getCompany() : null,
            $project->getSubmitterCompany(),
        ]);
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
            $this->canViewConfidential($project, $user)
            && in_array($user->getCompany(), $project->getLenderCompanies()->toArray())
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
        return $this->canViewConfidential($project, $user);
    }
}
