<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\ProjectParticipation;
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationVoter extends Voter
{
    public const ATTRIBUTE_REFUSE = 'refuse';
    /**
     * @var ProjectParticipationManager
     */
    private $projectParticipationManager;

    /**
     * ProjectParticipationVoter constructor.
     *
     * @param ProjectParticipationManager $projectParticipationManager
     */
    public function __construct(ProjectParticipationManager $projectParticipationManager)
    {
        $this->projectParticipationManager = $projectParticipationManager;
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed  $subject   The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool
     */
    protected function supports($attribute, $subject): bool
    {
        return $attribute === static::ATTRIBUTE_REFUSE && $subject instanceof ProjectParticipation;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string         $attribute
     * @param mixed          $subject
     * @param TokenInterface $token
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$this->projectParticipationManager->isConcernedClient($token->getUser(), $subject->getProject())) {
            return false;
        }

        return $this->canRefuse($subject);
    }

    /**
     * @param ProjectParticipation $participation
     *
     * @return bool
     */
    private function canRefuse(ProjectParticipation $participation): bool
    {
        //@todo replace by ProjectParticipaction::isOrganizer()
        return empty(
            array_intersect(
                [
                    ProjectParticipation::ROLE_PROJECT_ARRANGER,
                    ProjectParticipation::ROLE_PROJECT_DEPUTY_ARRANGER,
                    ProjectParticipation::ROLE_PROJECT_LOAN_OFFICER,
                    ProjectParticipation::ROLE_PROJECT_RUN,
                    ProjectParticipation::ROLE_PROJECT_SECURITY_TRUSTEE,
                ],
                $participation->getRoles()
            )
        );
    }
}
