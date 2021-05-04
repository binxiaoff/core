<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Repository\BorrowerMemberRepository;
use Unilend\Agency\Repository\ParticipationMemberRepository;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\UserRepository;
use Unilend\Core\Traits\ConstantsAwareTrait;

class ProjectRoleVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ROLE_BORROWER    = 'borrower';
    public const ROLE_PARTICIPANT = 'participant';
    public const ROLE_AGENT       = 'agent';

    private BorrowerMemberRepository $borrowerMemberRepository;

    private ParticipationMemberRepository $participationMemberRepository;

    private UserRepository $userRepository;

    public function __construct(
        UserRepository $userRepository,
        BorrowerMemberRepository $borrowerMemberRepository,
        ParticipationMemberRepository $participationMemberRepository
    ) {
        $this->borrowerMemberRepository      = $borrowerMemberRepository;
        $this->participationMemberRepository = $participationMemberRepository;
        $this->userRepository                = $userRepository;
    }

    public static function getAvailableRoles(): array
    {
        return static::getConstants('ROLE_');
    }

    /**
     * {@inheritDoc}
     */
    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof Project && \in_array($attribute, static::getAvailableRoles(), true);
    }

    /**
     * {@inheritDoc}
     *
     * @throws NonUniqueResultException
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        switch ($attribute) {
            case self::ROLE_AGENT:
                return $this->isAgent($subject, $token);

            case self::ROLE_BORROWER:
                return $this->isBorrower($subject, $token);

            case self::ROLE_PARTICIPANT:
                return $this->isParticipant($subject, $token);

            default:
                throw new \LogicException('This code should never be reached');
        }
    }

    /**
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    protected function isBorrower(Project $project, TokenInterface $token)
    {
        $user = $token->getUser();

        // Case of the refresh token
        $user = false === $user instanceof User ? $this->userRepository->findOneBy(['email' => $user->getUsername()]) : $user;

        // Borrower Member are not enabled until project is published
        return $this->borrowerMemberRepository->existsByProjectAndUser($project, $user) && $project->isPublished();
    }

    /**
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    protected function isParticipant(Project $project, TokenInterface $token)
    {
        // Participant can only be participant on project if project is published
        if (false === $project->isPublished()) {
            return false;
        }

        $staff = $token->hasAttribute('staff') ? $token->getAttribute('staff') : null;

        if (null === $staff) {
            return false;
        }

        // Fetch users whom connected user can get permission as he had them
        $managedUsers = $staff->getInheritedRightUsers();

        $company = $staff->getCompany();

        foreach ($managedUsers as $managedUser) {
            $participationMember = $this->participationMemberRepository->findByProjectAndCompanyAndUser($project, $company, $managedUser);

            if ($participationMember) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    protected function isAgent(Project $project, TokenInterface $token)
    {
        $staff = $token->hasAttribute('staff') ? $token->getAttribute('staff') : null;

        if (null === $staff || ($staff->getCompany() !== $project->getAgent())) {
            return false;
        }

        // Fetch users whom connected user can get permission as he had them
        $managedUsers = $staff->getInheritedRightUsers();

        $company = $staff->getCompany();

        foreach ($managedUsers as $managedUser) {
            if ($this->participationMemberRepository->findByProjectAndCompanyAndUser($project, $company, $managedUser)) {
                return true;
            }
        }

        return false;
    }
}
