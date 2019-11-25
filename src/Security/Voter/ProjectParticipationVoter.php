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
    public const ATTRIBUTE_BID    = 'bid';

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
        return in_array($attribute, [static::ATTRIBUTE_REFUSE, static::ATTRIBUTE_BID], true) && $subject instanceof ProjectParticipation;
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

        switch ($attribute) {
            case static::ATTRIBUTE_REFUSE:
                return $this->canRefuse($subject);
            case static::ATTRIBUTE_BID:
                return $this->canBid($subject);
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
        return false === $participation->isOrganizer();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @return bool
     */
    private function canBid(ProjectParticipation $projectParticipation)
    {
        return $projectParticipation->isArranger() || $projectParticipation->isParticipant();
    }
}
