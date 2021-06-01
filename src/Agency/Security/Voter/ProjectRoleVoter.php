<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Repository\AgentMemberRepository;
use Unilend\Agency\Repository\BorrowerMemberRepository;
use Unilend\Agency\Repository\ParticipationMemberRepository;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\UserRepository;
use Unilend\Core\Traits\ConstantsAwareTrait;

class ProjectRoleVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ROLE_BORROWER    = 'borrower';
    public const ROLE_PARTICIPANT = 'participant';
    public const ROLE_AGENT       = 'agent';

    public const ROLE_PRIMARY_PARTICIPANT   = 'primary_participant';
    public const ROLE_SECONDARY_PARTICIPANT = 'secondary_participant';

    private BorrowerMemberRepository $borrowerMemberRepository;

    private ParticipationMemberRepository $participationMemberRepository;

    private UserRepository $userRepository;

    private AgentMemberRepository $agentMemberRepository;

    public function __construct(
        UserRepository $userRepository,
        AgentMemberRepository $agentMemberRepository,
        BorrowerMemberRepository $borrowerMemberRepository,
        ParticipationMemberRepository $participationMemberRepository
    ) {
        $this->borrowerMemberRepository      = $borrowerMemberRepository;
        $this->participationMemberRepository = $participationMemberRepository;
        $this->userRepository                = $userRepository;
        $this->agentMemberRepository         = $agentMemberRepository;
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
                return $this->isPrimaryParticipant($subject, $token) || $this->isSecondaryParticipant($subject, $token);

            case self::ROLE_PRIMARY_PARTICIPANT:
                return $this->isPrimaryParticipant($subject, $token);

            case self::ROLE_SECONDARY_PARTICIPANT:
                return $this->isSecondaryParticipant($subject, $token);

            default:
                throw new \LogicException('This code should never be reached');
        }
    }

    /**
     * @throws NonUniqueResultException
     */
    private function isBorrower(Project $project, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Case of the refresh token
        $user = false === $user instanceof User ? $this->userRepository->findOneBy(['email' => $user->getUsername()]) : $user;

        // Borrower Member are not enabled until project is published
        return $this->borrowerMemberRepository->existsByProjectAndUser($project, $user) && $project->isPublished();
    }

    /**
     * @throws NonUniqueResultException
     */
    private function isPrimaryParticipant(Project $project, TokenInterface $token): bool
    {
        return $this->isParticipant($project, $token, false);
    }

    /**
     * @throws NonUniqueResultException
     */
    private function isSecondaryParticipant(Project $project, TokenInterface $token): bool
    {
        return $this->isParticipant($project, $token, true) && $project->hasSilentSyndication();
    }

    /**
     * @throws NonUniqueResultException
     */
    private function isAgent(Project $project, TokenInterface $token): bool
    {
        /** @var Staff $staff */
        $staff = $token->hasAttribute('staff') ? $token->getAttribute('staff') : null;

        if (null === $staff || ($staff->getCompany() !== $project->getAgentCompany())) {
            return false;
        }

        // Fetch users whom connected user can get permission as he had them
        $managedUsers = $staff->getManagedUsers();

        foreach ($managedUsers as $managedUser) {
            if ($this->agentMemberRepository->findOneByProjectAndUser($project, $managedUser)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws NonUniqueResultException
     */
    private function isParticipant(Project $project, TokenInterface $token, bool $secondary): bool
    {
        // Participant can only be participant on project if project is published
        if (false === $project->isPublished()) {
            return false;
        }

        $staff = $token->hasAttribute('staff') ? $token->getAttribute('staff') : null;

        if (null === $staff) {
            return false;
        }

        // Fetch managed users whom connected user can get permission as he had them
        $managedUsers = $staff->getManagedUsers();

        foreach ($managedUsers as $managedUser) {
            $participationMember = $this->participationMemberRepository->findByProjectAndCompanyAndUser($project, $staff->getCompany(), $managedUser);

            if ($participationMember) {
                $participation = $participationMember->getParticipation();

                return $secondary === $participation->isSecondary();
            }
        }

        return false;
    }
}
