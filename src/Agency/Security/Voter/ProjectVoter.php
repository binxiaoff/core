<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Repository\BorrowerMemberRepository;
use Unilend\Agency\Repository\ParticipationMemberRepository;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class ProjectVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';

    public const ATTRIBUTE_BORROWER    = 'borrower';
    public const ATTRIBUTE_PARTICIPANT = 'participant';
    public const ATTRIBUTE_AGENT       = 'agent';

    private BorrowerMemberRepository $borrowerMemberRepository;

    private ParticipationMemberRepository $participationMemberRepository;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        BorrowerMemberRepository $borrowerMemberRepository,
        ParticipationMemberRepository $participationMemberRepository
    ) {
        parent::__construct($authorizationChecker);
        $this->borrowerMemberRepository      = $borrowerMemberRepository;
        $this->participationMemberRepository = $participationMemberRepository;
    }

    /**
     * @throws Exception
     */
    protected function canView(Project $project, User $user): bool
    {
        return $this->canParticipant($project, $user) || $this->canBorrower($project, $user) || $this->canAgent($project, $user);
    }

    /**
     * Do not use can{Role} because object is not yet in database.
     */
    protected function canCreate(Project $project, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        // Est-ce que l'on devrai vérifier l'héritage pour la création des projets.
        return $staff->getCompany() === $project->getAgent() && $staff->hasAgencyProjectCreationPermission();
    }

    /**
     * @throws NonUniqueResultException
     */
    protected function canEdit(Project $project, User $user): bool
    {
        return $this->canAgent($project, $user);
    }

    /**
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    protected function canBorrower(Project $project, User $user)
    {
        return $this->borrowerMemberRepository->existsByProjectAndUser($project, $user) && $project->isPublished();
    }

    /**
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    protected function canParticipant(Project $project, User $user)
    {
        // Participant can only be participant on project if project is published
        if (false === $project->isPublished()) {
            return false;
        }

        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        // Fetch users whom connected user can get permission as he had them
        $managedUsers = $staff->getInheritedRightUsers();

        $company = $staff->getCompany();

        foreach ($managedUsers as $managedUser) {
            $participationMember = $this->participationMemberRepository->findByProjectAndCompanyAndUser($project, $company, $managedUser);

            if ($participationMember) {
                return false === $participationMember->getParticipation()->isArchived();
            }
        }

        return false;
    }

    /**
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    protected function canAgent(Project $project, User $user)
    {
        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        if ($staff->getCompany() !== $project->getAgent()) {
            return false;
        }

        // Fetch users whom connected user can get permission as he had them
        $managedUsers = $staff->getInheritedRightUsers();

        $company = $staff->getCompany();

        foreach ($managedUsers as $managedUser) {
            if ($this->participationMemberRepository->findByProjectAndCompanyAndUser($project, $company, $managedUser)) {
                return true; // Participant can only be participant on project if project is published
            }
        }

        return false;
    }
}
