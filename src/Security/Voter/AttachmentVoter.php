<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use LogicException;
use Monolog\Logger;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Attachment, Clients, Project, ProjectParticipationContact, ProjectStatus};
use Unilend\Repository\{AttachmentSignatureRepository, ProjectParticipationContactRepository};

class AttachmentVoter extends AbstractVoter
{
    public const ATTRIBUTE_DOWNLOAD = 'download';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var AttachmentSignatureRepository */
    private $attachmentSignatureRepository;

    /** @var ProjectParticipationContactRepository */
    private $participationContactRepository;

    /** @var Logger */
    private $logger;

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
        $this->attachmentSignatureRepository  = $attachmentSignatureRepository;
        $this->participationContactRepository = $participationContactRepository;
        $this->authorizationChecker           = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return $subject instanceof Attachment && parent::supports($attribute, $subject);
    }

    /**
     * @param Attachment $attachment
     * @param Clients    $user
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    protected function canDownload(Attachment $attachment, Clients $user): bool
    {
        $project   = $attachment->getProject();
        $signature = $this->attachmentSignatureRepository->findOneBy(['attachment' => $attachment, 'signatory' => $user]);

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
                        && ($this->hasValidatedOffer($contact) || $this->isAddedBeforeOfferCollected($project, $attachment));
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
    protected function getActiveParticipantParticipation(Project $project, Clients $user): ?ProjectParticipationContact
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
    protected function hasValidatedOffer(ProjectParticipationContact $contact): bool
    {
        return null !== ($participation = $contact->getProjectParticipation()) && $participation->hasValidatedOffer();
    }

    /**
     * @param Project    $project
     * @param Attachment $attachment
     *
     * @return bool
     */
    protected function isAddedBeforeOfferCollected(Project $project, Attachment $attachment): bool
    {
        $offerCollected = $project->getLastSpecificStatus(ProjectStatus::STATUS_OFFERS_COLLECTED);

        return null === $offerCollected || $attachment->getAdded() <= $offerCollected->getAdded();
    }
}
