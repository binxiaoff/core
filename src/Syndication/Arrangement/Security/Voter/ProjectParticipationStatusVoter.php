<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Security\Voter;

use DateTimeImmutable;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationStatus;

class ProjectParticipationStatusVoter extends AbstractEntityVoter
{
    protected function canCreate(ProjectParticipationStatus $projectParticipationStatus, User $user): bool
    {
        $staff   = $user->getCurrentStaff();
        $company = $staff ? $staff->getCompany() : null;

        if (null === $company) {
            return false;
        }

        // Check if the participation can be unarchived by the arranger
        if ($this->canBeUnarchivedByArranger($projectParticipationStatus, $company)) {
            return true;
        }

        $projectParticipation = $projectParticipationStatus->getProjectParticipation();

        if (false === $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $projectParticipation)) {
            return false;
        }

        /*
         * As there is a call to $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $projectParticipation) there is no need to retest
         * if there is a correct participationMember available (either a participationMember for current connected staff or a managed participationMember)
         *
         *  - arranger can put the status "ARCHIVED_BY_ARRANGER";
         *  - arranger can put the status "COMMITTEE_*" only for non-user participant;
         *  - participant can put the status "COMMITTEE_*" and "ARCHIVED_BY_PARTICIPANT"
         */
        switch ($projectParticipationStatus->getStatus()) {
            case ProjectParticipationStatus::STATUS_ARCHIVED_BY_ARRANGER:
                return $this->isArranger($projectParticipation, $company);

            case ProjectParticipationStatus::STATUS_COMMITTEE_PENDED:
            case ProjectParticipationStatus::STATUS_COMMITTEE_ACCEPTED:
            case ProjectParticipationStatus::STATUS_COMMITTEE_REJECTED:
                return $this->isParticipant($projectParticipation, $company)
                    || (
                        $this->isArranger($projectParticipation, $company)
                        && (
                            (
                                $projectParticipation->getParticipant()->isProspectAt($projectParticipationStatus->getAdded() ?? new DateTimeImmutable())
                                && $projectParticipation->getParticipant()->isSameGroup($projectParticipationStatus->getAddedBy()->getCompany())
                            )
                            || $projectParticipation->isArrangerParticipation()
                        )
                    );

            case ProjectParticipationStatus::STATUS_ARCHIVED_BY_PARTICIPANT:
                return $this->isParticipant($projectParticipation, $company);

            case ProjectParticipationStatus::STATUS_CREATED: // A projectParticipationStatus cannot be created
            default:
                return false;
        }
    }

    private function isParticipant(ProjectParticipation $participation, Company $currentConnectedCompany): bool
    {
        return $participation->getParticipant() === $currentConnectedCompany;
    }

    private function isArranger(ProjectParticipation $participation, Company $currentConnectedCompany): bool
    {
        return $participation->getProject()->getSubmitterCompany() === $currentConnectedCompany;
    }

    private function canBeUnarchivedByArranger(ProjectParticipationStatus $projectParticipationStatus, Company $company): bool
    {
        $projectParticipation = $projectParticipationStatus->getProjectParticipation();

        if (
            null === $projectParticipation->getCurrentStatus()
            || false === $this->isArranger($projectParticipation, $company)
            || ProjectParticipationStatus::STATUS_ARCHIVED_BY_ARRANGER !== $projectParticipation->getCurrentStatus()->getStatus()
        ) {
            return false;
        }

        $lastStatusBeforeArchiving = $projectParticipation->getLastStatusBeforeArchiving();

        return $lastStatusBeforeArchiving && $lastStatusBeforeArchiving->getStatus() === $projectParticipationStatus->getStatus();
    }
}
