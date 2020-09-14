<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, FileDownload, FileVersion, Project, ProjectFile, ProjectParticipation, ProjectParticipationMember, ProjectStatus, Staff};
use Unilend\Repository\{FileVersionSignatureRepository, ProjectFileRepository, ProjectParticipationMemberRepository, ProjectParticipationRepository, ProjectRepository};

class FileDownloadVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    /** @var FileVersionSignatureRepository */
    private FileVersionSignatureRepository $fileVersionSignatureRepository;
    /** @var ProjectParticipationMemberRepository */
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;
    /** @var ProjectFileRepository */
    private ProjectFileRepository $projectFileRepository;
    /** @var ProjectRepository */
    private ProjectRepository $projectRepository;
    /** @var ProjectParticipationRepository */
    private ProjectParticipationRepository $projectParticipationRepository;

    /**
     * @param AuthorizationCheckerInterface        $authorizationChecker
     * @param FileVersionSignatureRepository       $fileVersionSignatureRepository
     * @param ProjectParticipationMemberRepository $projectParticipationMemberRepository
     * @param ProjectFileRepository                $projectFileRepository
     * @param ProjectRepository                    $projectRepository
     * @param ProjectParticipationRepository       $projectParticipationRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        FileVersionSignatureRepository $fileVersionSignatureRepository,
        ProjectParticipationMemberRepository $projectParticipationMemberRepository,
        ProjectFileRepository $projectFileRepository,
        ProjectRepository $projectRepository,
        ProjectParticipationRepository $projectParticipationRepository
    ) {
        parent::__construct($authorizationChecker);
        $this->fileVersionSignatureRepository       = $fileVersionSignatureRepository;
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
        $this->projectFileRepository                = $projectFileRepository;
        $this->projectRepository                    = $projectRepository;
        $this->projectParticipationRepository = $projectParticipationRepository;
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
        $staff   = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        if (\in_array($type, ProjectFile::getProjectFileTypes(), true)) {
            $projectFile = $this->projectFileRepository->findOneBy(['file' => $file, 'type' => $type]);
            if (null === $projectFile) {
                return false;
            }
            $project = $projectFile->getProject();
        }

        if (\in_array($type, Project::getProjectFileTypes(), true)) {
            switch ($type) {
                case Project::PROJECT_FILE_TYPE_DESCRIPTION:
                    $project = $this->projectRepository->findOneBy(['descriptionDocument' => $file]);

                    break;
                case Project::PROJECT_FILE_TYPE_NDA:
                    $project = $this->projectRepository->findOneBy(['nda' => $file]);
                    if (null === $project) {
                        // Try to find the NDA in the participation,
                        // since the front may not know it's a specific NDA in case of getting it from ProjectParticipationMember::getAcceptableNdaVersion()
                        $projectParticipation = $this->projectParticipationRepository->findOneBy(['nda' => $file]);
                        $project = $projectParticipation ? $projectParticipation->getProject() : null;
                    }

                    break;
                default:
                    throw new InvalidArgumentException(sprintf('The type %s is not supported.', $type));
            }
        }

        if (ProjectParticipation::PROJECT_PARTICIPATION_FILE_TYPE_NDA === $type) {
            $projectParticipation = $this->projectParticipationRepository->findOneBy(['nda' => $file]);
            $project = $projectParticipation ? $projectParticipation->getProject() : null;
        }

        if (null === $project) {
            return false;
        }

        $signature = $this->fileVersionSignatureRepository->findOneBy(['fileVersion' => $fileDownload->getFileVersion(), 'signatory' => $staff]);

        if ($signature || $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)) {
            return true;
        }

        switch ($project->getCurrentStatus()->getStatus()) {
            case ProjectStatus::STATUS_INTEREST_EXPRESSION:
            case ProjectStatus::STATUS_PARTICIPANT_REPLY:
                return null !== $this->getActiveParticipantParticipation($project, $staff);
            case ProjectStatus::STATUS_ALLOCATION:
            case ProjectStatus::STATUS_CONTRACTUALISATION:
            case ProjectStatus::STATUS_SYNDICATION_FINISHED:
                return
                    null !== ($projectParticipationMember = $this->getActiveParticipantParticipation($project, $staff))
                    && ($this->hasValidatedOffer($projectParticipationMember) || $this->isAddedBeforeOfferCollected($project, $fileDownload->getFileVersion()));
            default:
                throw new LogicException('This code should not be reached');
        }
    }

    /**
     * Fetch an active (i.e. an interested) participation relating to a participant and not an organizer.
     *
     * @param Project $project
     * @param Staff   $staff
     *
     * @throws NonUniqueResultException
     *
     * @return ProjectParticipationMember|null
     */
    private function getActiveParticipantParticipation(Project $project, Staff $staff): ?ProjectParticipationMember
    {
        /** @var ProjectParticipationMember $projectParticipationMember */
        $projectParticipationMember = $this->projectParticipationMemberRepository->findByProjectAndStaff($project, $staff);

        return ($projectParticipationMember && $projectParticipationMember->getProjectParticipation()->isActive())
            ? $projectParticipationMember : null;
    }

    /**
     * @param ProjectParticipationMember $projectParticipationMember
     *
     * @return bool
     */
    private function hasValidatedOffer(ProjectParticipationMember $projectParticipationMember): bool
    {
        // Todo: the rule of validate offer need to be defined CALS-1702
        return null !== ($participation = $projectParticipationMember->getProjectParticipation()) && $participation->getAllocationFeeRate();
    }

    /**
     * @param Project     $project
     * @param FileVersion $fileVersion
     *
     * @return bool
     */
    private function isAddedBeforeOfferCollected(Project $project, FileVersion $fileVersion): bool
    {
        $statuses  = $project->getStatuses();
        $lastIndex = count($statuses) - 1;
        /** @var int $i */
        for ($i = $lastIndex; $i <= 0; --$i) {
            $status = $statuses[$i];
            if ($project->isInAllocationStep()) {
                return $fileVersion->getAdded() <= $status->getAdded();
            }
        }

        return true;
    }
}
