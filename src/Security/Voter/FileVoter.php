<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use LogicException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, FileVersion, Project, ProjectParticipationContact, ProjectStatus};
use Unilend\Repository\{AttachmentSignatureRepository, ProjectParticipationContactRepository};

class FileVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_DOWNLOAD = 'download';

    /** @var AttachmentSignatureRepository */
    private $attachmentSignatureRepository;

    /** @var ProjectParticipationContactRepository */
    private $participationContactRepository;

    /**
     * @param AuthorizationCheckerInterface         $authorizationChecker
     * @param AttachmentSignatureRepository         $attachmentSignatureRepository
     * @param ProjectParticipationContactRepository $participationContactRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        AttachmentSignatureRepository $attachmentSignatureRepository,
        ProjectParticipationContactRepository $participationContactRepository
    ) {
        parent::__construct($authorizationChecker);
        $this->attachmentSignatureRepository  = $attachmentSignatureRepository;
        $this->participationContactRepository = $participationContactRepository;
    }

    /**
     * @param FileVersion $fileVersion
     * @param Clients     $user
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    protected function canDownload(FileVersion $fileVersion, Clients $user): bool
    {
        //@todo change that
        $project   = $fileVersion->getProject();
        $signature = $this->attachmentSignatureRepository->findOneBy(['attachment' => $fileVersion, 'signatory' => $user]);

        if ($signature || $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project) || $project->getBorrowerCompany() === $user->getCompany()) {
            return true;
        }

        switch ($project->getCurrentStatus()->getStatus()) {
            case ProjectStatus::STATUS_PUBLISHED:
            case ProjectStatus::STATUS_INTERESTS_COLLECTED:
                return null !== $this->getActiveParticipantParticipation($project, $user);
            case ProjectStatus::STATUS_OFFERS_COLLECTED:
            case ProjectStatus::STATUS_CONTRACTS_SIGNED:
            case ProjectStatus::STATUS_REPAID:
                return
                    null !== ($contact = $this->getActiveParticipantParticipation($project, $user))
                        && ($this->hasValidatedOffer($contact) || $this->isAddedBeforeOfferCollected($project, $fileVersion));
            default:
                throw new LogicException('This code should not be reached');
        }
    }

    /**
     * Fetch an active (i.e. an interested) participation relating to a participant and not an organizer.
     *
     * @param Project $project
     * @param Clients $user
     *
     * @throws NonUniqueResultException
     *
     * @return ProjectParticipationContact|null
     */
    private function getActiveParticipantParticipation(Project $project, Clients $user): ?ProjectParticipationContact
    {
        /** @var ProjectParticipationContact $participationContact */
        $participationContact = $this->participationContactRepository->findByProjectAndClient($project, $user);

        return ($participationContact && false === $participationContact->getProjectParticipation()->isNotInterested())
            ? $participationContact : null;
    }

    /**
     * @param ProjectParticipationContact $contact
     *
     * @return bool
     */
    private function hasValidatedOffer(ProjectParticipationContact $contact): bool
    {
        return null !== ($participation = $contact->getProjectParticipation()) && $participation->hasValidatedOffer();
    }

    /**
     * @param Project     $project
     * @param FileVersion $fileVersion
     *
     * @return bool
     */
    private function isAddedBeforeOfferCollected(Project $project, FileVersion $fileVersion): bool
    {
        $offerCollected = $project->getLastSpecificStatus(ProjectStatus::STATUS_OFFERS_COLLECTED);

        return null === $offerCollected || $fileVersion->getAdded() <= $offerCollected->getAdded();
    }
}
