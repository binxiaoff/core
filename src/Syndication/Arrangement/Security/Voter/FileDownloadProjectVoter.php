<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Security\Voter;

use InvalidArgumentException;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\File;
use KLS\Core\Entity\FileDownload;
use KLS\Core\Entity\FileVersion;
use KLS\Core\Entity\User;
use KLS\Core\Repository\FileVersionSignatureRepository;
use KLS\Core\Security\Voter\FileDownloadVoterInterface;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectFile;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use KLS\Syndication\Arrangement\Repository\ProjectFileRepository;
use KLS\Syndication\Arrangement\Repository\ProjectParticipationRepository;
use KLS\Syndication\Arrangement\Repository\ProjectRepository;
use LogicException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FileDownloadProjectVoter implements FileDownloadVoterInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private FileVersionSignatureRepository $fileVersionSignatureRepository;
    private ProjectFileRepository $projectFileRepository;
    private ProjectRepository $projectRepository;
    private ProjectParticipationRepository $projectParticipationRepository;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        FileVersionSignatureRepository $fileVersionSignatureRepository,
        ProjectFileRepository $projectFileRepository,
        ProjectRepository $projectRepository,
        ProjectParticipationRepository $projectParticipationRepository
    ) {
        $this->authorizationChecker           = $authorizationChecker;
        $this->fileVersionSignatureRepository = $fileVersionSignatureRepository;
        $this->projectFileRepository          = $projectFileRepository;
        $this->projectRepository              = $projectRepository;
        $this->projectParticipationRepository = $projectParticipationRepository;
    }

    public function supports(FileDownload $fileDownload): bool
    {
        $type = $fileDownload->getType();

        return
            \in_array($type, ProjectFile::getProjectFileTypes(), true)
            || \in_array($type, Project::getProjectFileTypes(), true)
            || ProjectParticipation::PROJECT_PARTICIPATION_FILE_TYPE_NDA === $type
        ;
    }

    public function canCreate(FileDownload $fileDownload, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        $file    = $fileDownload->getFileVersion()->getFile();
        $type    = $fileDownload->getType();
        $project = null;

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
                    $project = $this->projectRepository->findOneBy(['termSheet' => $file]);

                    break;

                case Project::PROJECT_FILE_TYPE_NDA:
                    $project = $this->projectRepository->findOneBy(['nda' => $file]);
                    if (null === $project) {
                        // Try to find the NDA in the participation,
                        // since the front may not know it's a specific NDA in case of getting it from ProjectParticipationMember::getAcceptableNdaVersion()
                        $project = $this->findProjectByNDAFile($file, $staff->getCompany());
                    }

                    break;

                default:
                    throw new InvalidArgumentException(\sprintf('The type %s is not supported.', $type));
            }
        }

        if (ProjectParticipation::PROJECT_PARTICIPATION_FILE_TYPE_NDA === $type) {
            $project = $this->findProjectByNDAFile($file, $staff->getCompany());
        }

        if (null === $project) {
            return false;
        }

        $signature = $this->fileVersionSignatureRepository->findOneBy(['fileVersion' => $fileDownload->getFileVersion(), 'signatory' => $staff]);

        if ($signature || $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)) {
            return true;
        }

        if ($this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $project->getArrangerProjectParticipation())) {
            return true;
        }

        $participation = $this->projectParticipationRepository->findOneBy(['project' => $project, 'participant' => $staff->getCompany()]);

        switch ($project->getCurrentStatus()->getStatus()) {
            case ProjectStatus::STATUS_INTEREST_EXPRESSION:
            case ProjectStatus::STATUS_PARTICIPANT_REPLY:
                return $participation && $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $participation);

            case ProjectStatus::STATUS_ALLOCATION:
            case ProjectStatus::STATUS_CONTRACTUALISATION:
            case ProjectStatus::STATUS_SYNDICATION_FINISHED:
            case ProjectStatus::STATUS_SYNDICATION_CANCELLED:
                return $participation
                    && $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $participation)
                    && ($this->hasValidatedOffer($participation) || $this->isAddedBeforeOfferCollected($project, $fileDownload->getFileVersion()));

            default:
                throw new LogicException('This code should not be reached');
        }
    }

    private function hasValidatedOffer(ProjectParticipation $projectParticipation): bool
    {
        // Todo: the rule of validate offer need to be defined CALS-1702
        return (bool) $projectParticipation->getAllocationFeeRate();
    }

    private function isAddedBeforeOfferCollected(Project $project, FileVersion $fileVersion): bool
    {
        $statuses  = $project->getStatuses();
        $lastIndex = \count($statuses) - 1;

        /** @var int $i */
        for ($i = $lastIndex; $i <= 0; --$i) {
            $status = $statuses[$i];
            if ($project->isInAllocationStep()) {
                return $fileVersion->getAdded() <= $status->getAdded();
            }
        }

        return true;
    }

    private function findProjectByNDAFile(File $nda, Company $currentStaffCompany): ?Project
    {
        $projectParticipation = $this->projectParticipationRepository->findOneBy(['nda' => $nda]);
        $project              = $projectParticipation ? $projectParticipation->getProject() : null;

        // Only the arranger or the participant of the participation can download the specific NDA
        return $project && ($project->getArranger() === $currentStaffCompany || $projectParticipation->getParticipant() === $currentStaffCompany) ? $project : null;
    }
}
