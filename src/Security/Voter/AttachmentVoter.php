<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use Exception;
use LogicException;
use Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\{Attachment, Clients, Project, ProjectParticipationContact, ProjectStatus};
use Unilend\Repository\{AttachmentSignatureRepository, ProjectParticipationContactRepository};
use Unilend\Traits\ConstantsAwareTrait;

class AttachmentVoter extends Voter
{
    use ConstantsAwareTrait;

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
        $attributes = self::getConstants('ATTRIBUTE_');

        return $subject instanceof Attachment && in_array($attribute, $attributes, true);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function voteOnAttribute($attribute, $attachment, TokenInterface $token): bool
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        switch ($attribute) {
            case self::ATTRIBUTE_DOWNLOAD:
                return $this->canDownload($attachment, $user);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param Attachment $attachment
     * @param Clients    $user
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    private function canDownload(Attachment $attachment, Clients $user): bool
    {
        $project = $attachment->getProject();
        if ($project->getBorrowerCompany() === $user->getCompany()) {
            return true;
        }

        $signature = $this->attachmentSignatureRepository->findOneBy([
            'attachment' => $attachment,
            'signatory'  => $user,
        ]);

        if ($signature) {
            return true;
        }

        if ($this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)) {
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
     * @param Project    $project
     * @param Attachment $attachment
     *
     * @return bool
     */
    private function isAddedBeforeOfferCollected(Project $project, Attachment $attachment): bool
    {
        $offerCollected = $project->getLastSpecificStatus(ProjectStatus::STATUS_OFFERS_COLLECTED);

        return null === $offerCollected || $attachment->getAdded() <= $offerCollected->getAdded();
    }
}
