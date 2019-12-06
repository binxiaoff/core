<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use Exception;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\{Clients, Embeddable\Permission, Project, ProjectParticipationContact};
use Unilend\Repository\ProjectParticipationContactRepository;
use Unilend\Traits\ConstantsAwareTrait;

class ProjectVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_VIEW                     = 'view';
    public const ATTRIBUTE_VIEW_CONFIDENTIALITY_DOC = 'view_confidentiality_doc';
    public const ATTRIBUTE_EDIT                     = 'edit';
    public const ATTRIBUTE_MANAGE_TRANCHE_OFFER     = 'manage_tranche_offer';
    public const ATTRIBUTE_RATE                     = 'rate';
    public const ATTRIBUTE_CREATE_TRANCHE_OFFER     = 'create_tranche_offer';
    public const ATTRIBUTE_COMMENT                  = 'comment';

    /** @var ProjectParticipationContactRepository */
    private $projectParticipationContactRepository;

    /**
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     */
    public function __construct(ProjectParticipationContactRepository $projectParticipationContactRepository)
    {
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

        if (false === $subject instanceof Project) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function voteOnAttribute($attribute, $project, TokenInterface $token): bool
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        switch ($attribute) {
            case self::ATTRIBUTE_VIEW:
                return $this->canView($project, $user);
            case self::ATTRIBUTE_VIEW_CONFIDENTIALITY_DOC:
                return $this->canViewConfidentialityDocument($project, $user);
            case self::ATTRIBUTE_EDIT:
                return $this->canEdit($project, $user);
            case self::ATTRIBUTE_MANAGE_TRANCHE_OFFER:
                return $this->canManageTrancheOffer($project, $user);
            case self::ATTRIBUTE_RATE:
                return $this->canRate($project, $user);
            case self::ATTRIBUTE_CREATE_TRANCHE_OFFER:
                return $this->canCreateTrancheOffer($project, $user);
            case self::ATTRIBUTE_COMMENT:
                return $this->canComment($project, $user);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    private function canView(Project $project, Clients $user): bool
    {
        if ($this->canEdit($project, $user)) {
            return true;
        }

        $projectParticipationContact = $this->projectParticipationContactRepository->findByProjectAndClient($project, $user);

        return  $projectParticipationContact && (false === $project->isConfidential() || null !== $projectParticipationContact->getConfidentialityAccepted());
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    private function canViewConfidentialityDocument(Project $project, Clients $user): bool
    {
        if ($this->canEdit($project, $user) || $this->canView($project, $user)) {
            return true;
        }

        return null !== $this->projectParticipationContactRepository->findByProjectAndClient($project, $user);
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    private function canEdit(Project $project, Clients $user): bool
    {
        if ($user->getCompany() === $project->getSubmitterCompany()) {
            return true;
        }

        $participationContact = $this->getProjectParticipationContact($project, $user);

        return $participationContact && $participationContact->getProjectParticipation()->getPermission()->has(Permission::PERMISSION_EDIT);
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    private function canManageTrancheOffer(Project $project, Clients $user): bool
    {
        $participationContact = $this->getProjectParticipationContact($project, $user);

        return $participationContact && $participationContact->getProjectParticipation()->isArranger();
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    private function canRate(Project $project, Clients $user): bool
    {
        $participationContact = $this->getProjectParticipationContact($project, $user);

        return $participationContact && $participationContact->getProjectParticipation()->isRun();
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    private function canCreateTrancheOffer(Project $project, Clients $user): bool
    {
        $participationContact = $this->getProjectParticipationContact($project, $user);

        return $participationContact && $participationContact->getProjectParticipation()->isParticipant();
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    private function canComment(Project $project, Clients $user): bool
    {
        return $this->canView($project, $user);
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws NonUniqueResultException
     *
     * @return ProjectParticipationContact
     */
    private function getProjectParticipationContact(Project $project, Clients $user): ProjectParticipationContact
    {
        return $this->projectParticipationContactRepository->findByProjectAndClient($project, $user);
    }
}
