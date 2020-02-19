<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, Project, ProjectOrganizer, ProjectParticipation, ProjectParticipationContact, ProjectParticipationOffer};
use Unilend\Repository\{ProjectOrganizerRepository, ProjectParticipationContactRepository};

class ProjectParticipationOfferVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';

    /** @var ProjectOrganizerRepository */
    private $projectOrganizerRepository;
    /** @var ProjectParticipationContactRepository */
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
        parent::__construct($authorizationChecker);
        $this->projectOrganizerRepository            = $projectOrganizerRepository;
        $this->projectParticipationContactRepository = $projectParticipationContactRepository;
    }

    /**
     * @param ProjectParticipationOffer $subject
     * @param Clients                   $user
     *
     * @return bool
     */
    protected function isGrantedAll($subject, Clients $user): bool
    {
        $projectParticipation = $subject->getProjectParticipation();
        $projectOrganizer     = $this->getProjectOrganizer($projectParticipation->getProject(), $user);

        return ($projectOrganizer && $projectOrganizer->isArranger())
            || null !== $this->getParticipationContact($projectParticipation, $user);
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
