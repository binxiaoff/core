<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\TeamRepository;

class TeamVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_EDIT = 'edit';

    /** @var TeamRepository */
    private TeamRepository $teamRepository;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TeamRepository                $teamRepository
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TeamRepository $teamRepository)
    {
        parent::__construct($authorizationChecker);
        $this->teamRepository = $teamRepository;
    }

    /**
     * @param User $user
     * @param Team $team
     *
     * @return bool
     */
    public function canCreate(User $user, Team $team)
    {
        $submitterStaff = $user->getCurrentStaff();

        if (null === $submitterStaff) {
            return false;
        }

        if (false === $submitterStaff->isManager()) {
            return false;
        }

        return $this->teamRepository->isRootPathNode($submitterStaff->getTeam(), $team->getParent());
    }

    /**
     * @param User $user
     * @param Team $team
     *
     * @return bool
     */
    public function canEdit(User $user, Team $team)
    {
        $submitterStaff = $user->getCurrentStaff();

        if (null === $submitterStaff) {
            return false;
        }

        if (false === $submitterStaff->isManager()) {
            return false;
        }

        return $this->teamRepository->isRootPathNode($submitterStaff->getTeam(), $team);
    }
}
