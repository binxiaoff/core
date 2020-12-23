<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\{Company, File, FileDownload, FileVersion, Message, MessageFile, Staff, User};
use Unilend\Core\Repository\FileVersionSignatureRepository;
use Unilend\Core\Repository\MessageFileRepository;
use Unilend\Syndication\Entity\{Project, ProjectFile, ProjectParticipation, ProjectParticipationMember, ProjectStatus};
use Unilend\Syndication\Repository\{ProjectFileRepository, ProjectParticipationMemberRepository, ProjectParticipationRepository, ProjectRepository};
use Unilend\Syndication\Security\Voter\ProjectVoter;

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
    /** @var MessageFileRepository */
    private MessageFileRepository $messageFileRepository;

    /**
     * FileDownloadVoter constructor.
     *
     * @param AuthorizationCheckerInterface        $authorizationChecker
     * @param FileVersionSignatureRepository       $fileVersionSignatureRepository
     * @param ProjectParticipationMemberRepository $projectParticipationMemberRepository
     * @param ProjectFileRepository                $projectFileRepository
     * @param ProjectRepository                    $projectRepository
     * @param ProjectParticipationRepository       $projectParticipationRepository
     * @param MessageFileRepository                $messageFileRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        FileVersionSignatureRepository $fileVersionSignatureRepository,
        ProjectParticipationMemberRepository $projectParticipationMemberRepository,
        ProjectFileRepository $projectFileRepository,
        ProjectRepository $projectRepository,
        ProjectParticipationRepository $projectParticipationRepository,
        MessageFileRepository $messageFileRepository
    ) {
        parent::__construct($authorizationChecker);
        $this->fileVersionSignatureRepository       = $fileVersionSignatureRepository;
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
        $this->projectFileRepository                = $projectFileRepository;
        $this->projectRepository                    = $projectRepository;
        $this->projectParticipationRepository       = $projectParticipationRepository;
        $this->messageFileRepository                = $messageFileRepository;
    }

    /**
     * @param FileDownload $fileDownload
     * @param User         $user
     *
     * @return bool
     */
    protected function fulfillPreconditions($fileDownload, User $user): bool
    {
        return $fileDownload->getFileVersion()->getFile() && $fileDownload->getFileVersion() === $fileDownload->getFileVersion()->getFile()->getCurrentFileVersion();
    }

    /**
     * @param FileDownload $fileDownload
     * @param User         $user
     *
     * @return bool

     **@throws NonUniqueResultException
     *
     */
    protected function canCreate(FileDownload $fileDownload, User $user): bool
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
                        $project = $this->findProjectByNDAFile($file, $staff->getCompany());
                    }

                    break;
                default:
                    throw new InvalidArgumentException(sprintf('The type %s is not supported.', $type));
            }
        }

        if (Message::FILE_TYPE_MESSAGE_ATTACHMENT === $type) {
            return $this->isAllowedToDownloadMessageFile($file, $staff);
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
     * @param File  $file
     * @param Staff $staff
     *
     * @return bool
     */
    private function isAllowedToDownloadMessageFile(File $file, Staff $staff): bool
    {
        $messageFile = $this->messageFileRepository->getMessageFileByFileAndRecipient($file, $staff);

        if ($messageFile instanceof MessageFile) {
            return $this->authorizationChecker->isGranted(MessageVoter::ATTRIBUTE_VIEW, $messageFile->getMessage());
        }

        return false;
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

    /**
     * @param File    $nda
     * @param Company $currentStaffCompany
     *
     * @return Project|null
     */
    private function findProjectByNDAFile(File $nda, Company $currentStaffCompany): ?Project
    {
        $projectParticipation = $this->projectParticipationRepository->findOneBy(['nda' => $nda]);
        $project = $projectParticipation ? $projectParticipation->getProject() : null;

        // Only the arranger or the participant of the participation can download the specific NDA
        return $project && ($project->getArranger() === $currentStaffCompany || $projectParticipation->getParticipant() === $currentStaffCompany) ? $project : null;
    }
}
