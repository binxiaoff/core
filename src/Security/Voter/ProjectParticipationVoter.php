<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\{AuthorizationCheckerInterface, Voter\Voter};
use Unilend\Entity\Clients;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectOrganizer;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationContact;
use Unilend\Repository\ProjectOrganizerRepository;
use Unilend\Repository\ProjectParticipationContactRepository;
use Unilend\Traits\ConstantsAwareTrait;

class ProjectParticipationVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_VIEW = 'view';
    public const ATTRIBUTE_EDIT = 'edit';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;
    /**
     * @var ProjectOrganizerRepository
     */
    private $projectOrganizerRepository;
    /**
     * @var ProjectParticipationContactRepository
     */
    private $projectParticipationContactRepository;

    /**
     * @param AuthorizationCheckerInterface         $authorizationChecker
     * @param ProjectOrganizerRepository            $projectOrganizerRepository
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ProjectOrganizerRepository $projectOrganizerRepository,
        ProjectParticipationContactRepository $projectParticipationContactRepository
    ) {
        $this->authorizationChecker                  = $authorizationChecker;
        $this->projectOrganizerRepository            = $projectOrganizerRepository;
        $this->projectParticipationContactRepository = $projectParticipationContactRepository;
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

        if (false === $subject instanceof ProjectParticipation) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return true;
        }

        switch ($attribute) {
            case self::ATTRIBUTE_VIEW:
            case self::ATTRIBUTE_EDIT:
                return $this->getProjectOrganizer($subject->getProject(), $user) || $this->getParticipationContact($subject, $user);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @return ProjectOrganizer|null
     */
    private function getProjectOrganizer(Project $project, Clients $user): ?ProjectOrganizer
    {
        return $this->projectOrganizerRepository->findOneBy(['project' => $project, 'company' => $user->getCompany()]);
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return ProjectParticipationContact
     */
    private function getParticipationContact(ProjectParticipation $projectParticipation, Clients $user): ProjectParticipationContact
    {
        return $this->projectParticipationContactRepository->findOneBy(['projectParticipation' => $projectParticipation, 'client' => $user]);
    }
}
