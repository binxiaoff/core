<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use LogicException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, Project, ProjectOrganizer, ProjectParticipation, ProjectParticipationContact, ProjectStatus};
use Unilend\Repository\{ProjectOrganizerRepository, ProjectParticipationContactRepository};
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;
    /** @var ProjectOrganizerRepository */
    private $projectOrganizerRepository;
    /** @var ProjectParticipationContactRepository */
    private $projectParticipationContactRepository;
    /** @var ProjectParticipationManager */
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
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    protected function canView(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        $project          = $projectParticipation->getProject();
        $projectOrganizer = $this->getProjectOrganizer($projectParticipation->getProject(), $user);

        if ($this->authorizationChecker->isGranted(Clients::ROLE_ADMIN) || ($projectOrganizer && $projectOrganizer->isArranger())) {
            return true;
        }

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
    protected function canEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        $projectOrganizer = $this->getProjectOrganizer($projectParticipation->getProject(), $user);

        if ($this->authorizationChecker->isGranted(Clients::ROLE_ADMIN) || ($projectOrganizer && $projectOrganizer->isArranger())) {
            return true;
        }

        return null !== $this->getParticipationContact($projectParticipation, $user);
    }

    /**
     * @see https://lafabriquebyca.atlassian.net/browse/CALS-759
     *
     * @param ProjectParticipation $subject
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipation $subject, Clients $user): bool
    {
        $company   = $subject->getCompany();
        $blacklist = array_map('strtolower', ProjectParticipation::BLACKLISTED_COMPANIES);

        $projectOrganizer = $this->getProjectOrganizer($subject->getProject(), $user);

        if ($this->authorizationChecker->isGranted(Clients::ROLE_ADMIN) || ($projectOrganizer && $projectOrganizer->isArranger())) {
            return true;
        }

        return false === \in_array(mb_strtolower($company->getName()), $blacklist, true) && $this->authorizationChecker->isGranted('edit', $subject->getProject());
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
