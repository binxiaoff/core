<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use LogicException;
use Prophecy\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, FileDownload, FileVersion, Project, ProjectFile, ProjectParticipationContact, ProjectStatus};
use Unilend\Repository\{FileVersionSignatureRepository, ProjectFileRepository, ProjectParticipationContactRepository, ProjectRepository};

class FileDownloadVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    /** @var FileVersionSignatureRepository */
    private $fileVersionSignatureRepository;

    /** @var ProjectParticipationContactRepository */
    private $participationContactRepository;
    /**
     * @var ProjectFileRepository
     */
    private $projectFileRepository;
    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    /**
     * @param AuthorizationCheckerInterface         $authorizationChecker
     * @param FileVersionSignatureRepository        $fileVersionSignatureRepository
     * @param ProjectParticipationContactRepository $participationContactRepository
     * @param ProjectFileRepository                 $projectFileRepository
     * @param ProjectRepository                     $projectRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        FileVersionSignatureRepository $fileVersionSignatureRepository,
        ProjectParticipationContactRepository $participationContactRepository,
        ProjectFileRepository $projectFileRepository,
        ProjectRepository $projectRepository
    ) {
        parent::__construct($authorizationChecker);
        $this->fileVersionSignatureRepository = $fileVersionSignatureRepository;
        $this->participationContactRepository = $participationContactRepository;
        $this->projectFileRepository          = $projectFileRepository;
        $this->projectRepository              = $projectRepository;
    }

    /**
     * @param FileDownload $fileDownload
     * @param Clients      $user
     *
     * @return bool
     */
    protected function fulfillPreconditions($fileDownload, Clients $user): bool
    {
        return $fileDownload->getFileVersion()->getFile() && $fileDownload->getFileVersion() === $fileDownload->getFileVersion()->getFile()->getCurrentFileVersion();
    }

    /**
     * @param FileDownload $fileDownload
     * @param Clients      $user
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    protected function canCreate(FileDownload $fileDownload, Clients $user): bool
    {
        $file    = $fileDownload->getFileVersion()->getFile();
        $type    = $fileDownload->getType();
        $project = null;

        if (in_array($type, ProjectFile::getProjectFileTypes(), true)) {
            $projectFile = $this->projectFileRepository->findOneBy(['file' => $file, 'type' => $type]);
            if (null === $projectFile) {
                return false;
            }
            $project = $projectFile->getProject();
        }

        if (in_array($type, Project::getProjectFileTypes(), true)) {
            switch ($type) {
                case Project::PROJECT_FILE_TYPE_DESCRIPTION:
                    $project = $this->projectRepository->findOneBy(['descriptionDocument' => $file]);

                    break;
                case Project::PROJECT_FILE_TYPE_CONFIDENTIALITY:
                    $project = $this->projectRepository->findOneBy(['confidentialityDisclaimer' => $file]);

                    break;
                default:
                    throw new InvalidArgumentException(sprintf('The type %s is not supported.', $type));
            }
        }

        if (null === $project) {
            return false;
        }

        $signature = $this->fileVersionSignatureRepository->findOneBy(['fileVersion' => $fileDownload->getFileVersion(), 'signatory' => $user->getCurrentStaff()]);

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
                    && ($this->hasValidatedOffer($contact) || $this->isAddedBeforeOfferCollected($project, $fileDownload->getFileVersion()));
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
