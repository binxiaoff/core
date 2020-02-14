<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, Project, ProjectOrganizer, ProjectParticipation, ProjectParticipationContact, ProjectStatus};
use Unilend\Repository\{ProjectOrganizerRepository, ProjectParticipationContactRepository};
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationVoter extends AbstractVoter
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
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return $subject instanceof ProjectParticipation && parent::supports($subject, $subject);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $this->getUser($token);

        // TODO It might be interessing to put this condition in ProjectVoter
        if (null !== $user || $subject->getProject()->getCurrentStatus()->getStatus() > ProjectStatus::STATUS_INTERESTS_COLLECTED) {
            return false;
        }

        $projectOrganizer = $this->getProjectOrganizer($subject->getProject(), $user);

        return $this->authorizationChecker->isGranted(Clients::ROLE_ADMIN)
            || ($projectOrganizer && $projectOrganizer->isArranger())
            || parent::voteOnAttribute($attribute, $subject, $token);
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
    protected function canEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        return null !== $this->getParticipationContact($projectParticipation, $user);
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @return ProjectOrganizer|null
     */
    protected function getProjectOrganizer(Project $project, Clients $user): ?ProjectOrganizer
    {
        return $this->projectOrganizerRepository->findOneBy(['project' => $project, 'company' => $user->getCompany()]);
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return ProjectParticipationContact|null
     */
    protected function getParticipationContact(ProjectParticipation $projectParticipation, Clients $user): ?ProjectParticipationContact
    {
        return $this->projectParticipationContactRepository->findOneBy(['projectParticipation' => $projectParticipation, 'client' => $user]);
    }

    /**
     * @see https://lafabriquebyca.atlassian.net/browse/CALS-759
     *
     * @param ProjectParticipation $subject
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipation $subject): bool
    {
        $company   = $subject->getCompany();
        $blacklist = array_map('strtolower', ProjectParticipation::BLACKLISTED_COMPANIES);

        return false === \in_array(mb_strtolower($company->getName()), $blacklist, true) && $this->authorizationChecker->isGranted('edit', $subject->getProject());
    }
}
