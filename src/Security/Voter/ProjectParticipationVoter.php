<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
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
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;
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
     * @var ProjectParticipationManager
     */
    private $projectParticipationManager;

    /**
     * @param AuthorizationCheckerInterface         $authorizationChecker
     * @param ProjectOrganizerRepository            $projectOrganizerRepository
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     * @param ProjectParticipationManager           $projectParticipationManager
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ProjectOrganizerRepository $projectOrganizerRepository,
        ProjectParticipationContactRepository $projectParticipationContactRepository,
        ProjectParticipationManager $projectParticipationManager
    ) {
        $this->authorizationChecker                  = $authorizationChecker;
        $this->projectOrganizerRepository            = $projectOrganizerRepository;
        $this->projectParticipationContactRepository = $projectParticipationContactRepository;
        $this->projectParticipationManager           = $projectParticipationManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return $subject instanceof ProjectParticipation && in_array($attribute, self::getConstants('ATTRIBUTE_'), true);
    }

    /**
     * {@inheritdoc}
     *
     * @throws NonUniqueResultException
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        $projectOrganizer = $this->getProjectOrganizer($subject->getProject(), $user);

        if (
            $this->authorizationChecker->isGranted(Clients::ROLE_ADMIN)
            || (
                $projectOrganizer
                && $projectOrganizer->isArranger()
            )
        ) {
            return true;
        }

        switch ($attribute) {
            case self::ATTRIBUTE_VIEW:
                return $this->canView($subject, $user);
            case self::ATTRIBUTE_EDIT:
                return $this->canEdit($subject, $user);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    private function canView(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        $project = $projectParticipation->getProject();

        switch ($project->getOfferVisibility()) {
            case Project::OFFER_VISIBILITY_PRIVATE:
                return null !== $this->getParticipationContact($projectParticipation, $user);
            case Project::OFFER_VISIBILITY_PARTICIPANT:
            case Project::OFFER_VISIBILITY_PUBLIC:
                return $this->projectParticipationManager->isParticipant($user, $project);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    private function canEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        return null !== $this->getParticipationContact($projectParticipation, $user);
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
     * @return ProjectParticipationContact|null
     */
    private function getParticipationContact(ProjectParticipation $projectParticipation, Clients $user): ?ProjectParticipationContact
    {
        return $this->projectParticipationContactRepository->findOneBy(['projectParticipation' => $projectParticipation, 'client' => $user]);
    }
}
