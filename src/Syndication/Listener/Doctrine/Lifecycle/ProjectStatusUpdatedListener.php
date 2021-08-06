<?php

declare(strict_types=1);

namespace Unilend\Syndication\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Exception;
use RuntimeException;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\Embeddable\Offer;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Entity\ProjectParticipationStatus;
use Unilend\Syndication\Entity\ProjectParticipationTranche;
use Unilend\Syndication\Entity\ProjectStatus;
use Unilend\Syndication\Entity\Tranche;

/**
 * TODO Refactor because we should not use doctrine for automatic status action.
 */
class ProjectStatusUpdatedListener
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @throws Exception
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Project) {
                $this->onStatusChange($entity, $args->getEntityManager());
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Project) {
                $this->onStatusChange($entity, $args->getEntityManager());
            }
        }
    }

    /**
     * @throws Exception
     */
    private function onStatusChange(Project $project, EntityManager $em): void
    {
        $currentStatus = $project->getCurrentStatus();

        if (ProjectStatus::STATUS_PARTICIPANT_REPLY === $currentStatus->getStatus()) {
            $this->createMissingArrangerParticipationTranche($project, $em);
        }

        if (ProjectStatus::STATUS_ALLOCATION === $currentStatus->getStatus()) {
            $this->transferInvitationReply($project, $em);
            $this->archiveUndecidedParticipant($project, $em);
        }
    }

    /**
     * @throws Exception
     */
    private function transferInvitationReply(Project $project, EntityManager $em): void
    {
        $statuses = $project->getStatuses();

        // This works because we do not add currentStatus to the statuses list
        $previousStatus = $statuses->last();

        // Ensure to have the correct previous status (in case current status have been added to statuses array)
        // $previousStatus = $previousStatus === $projectStatus ? $statuses[$statuses->count() - 2] : $previousStatus;

        if (false === $previousStatus || ProjectStatus::STATUS_PARTICIPANT_REPLY !== $previousStatus->getStatus()) {
            return;
        }

        $uow                                 = $em->getUnitOfWork();
        $projectParticipationTrancheMetaData = $em->getClassMetadata(ProjectParticipationTranche::class);
        $projectParticipations               = $project->getProjectParticipations();

        foreach ($projectParticipations as $projectParticipation) {
            if (ProjectParticipationStatus::STATUS_COMMITTEE_ACCEPTED === $projectParticipation->getCurrentStatus()->getStatus()) {
                foreach ($projectParticipation->getProjectParticipationTranches() as $projectParticipationTranche) {
                    $invitationReply = $projectParticipationTranche->getInvitationReply();
                    $allocationOffer = $projectParticipationTranche->getAllocation();

                    if ($invitationReply->isValid() && false === $allocationOffer->isValid()) {
                        $projectParticipationTranche->setAllocation(new Offer($invitationReply->getMoney()));
                        $uow->computeChangeSet($projectParticipationTrancheMetaData, $projectParticipationTranche);
                    }
                }
            }
        }
    }

    /**
     * @throws ORMException
     * @throws Exception
     */
    private function createMissingArrangerParticipationTranche(Project $project, EntityManager $em): void
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $staff = $user->getCurrentStaff();

        $tranches = $project->getTranches();

        $arrangerParticipation = $project->getArrangerProjectParticipation();
        $uow                   = $em->getUnitOfWork();

        $projectParticipationTrancheClassMetadata = $em->getClassMetadata(ProjectParticipationTranche::class);

        foreach ($tranches as $tranche) {
            if (false === $tranche->isSyndicated() && Tranche::UNSYNDICATED_FUNDER_TYPE_ARRANGER === $tranche->getUnsyndicatedFunderType()) {
                $projectParticipationTranches = $tranche->getProjectParticipationTranches();

                $exist = $projectParticipationTranches->exists(
                    static function (int $index, ProjectParticipationTranche $projectParticipationTranche) use ($arrangerParticipation) {
                        return $arrangerParticipation === $projectParticipationTranche->getProjectParticipation();
                    }
                );

                if (false === $exist) {
                    $projectParticipationTranche = new ProjectParticipationTranche($arrangerParticipation, $tranche, $staff);
                    $em->persist($projectParticipationTranche);
                    $uow->computeChangeSet($projectParticipationTrancheClassMetadata, $projectParticipationTranche);
                }
            }
        }
    }

    /**
     * @throws ORMException
     * @throws Exception
     */
    private function archiveUndecidedParticipant(Project $project, EntityManager $em): void
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $staff = $user->getCurrentStaff();

        $participations = $project->getProjectParticipations();

        $projectParticipationClassMetadata       = $em->getClassMetadata(ProjectParticipation::class);
        $projectParticipationStatusClassMetadata = $em->getClassMetadata(ProjectParticipationStatus::class);

        $uow = $em->getUnitOfWork();

        foreach ($participations as $participation) {
            if ($participation->isArrangerParticipation()) {
                continue;
            }

            $currentStatus = $participation->getCurrentStatus();

            if (null === $currentStatus) {
                throw new RuntimeException(\sprintf('The participation with the publicId %s should have a current status', $participation->getPublicId()));
            }

            $status = $currentStatus->getStatus();

            if (ProjectParticipationStatus::STATUS_COMMITTEE_PENDED === $status || ProjectParticipationStatus::STATUS_CREATED === $status) {
                $archivedStatus = new ProjectParticipationStatus($participation, ProjectParticipationStatus::STATUS_ARCHIVED_BY_ARRANGER, $staff);
                $participation->setCurrentStatus($archivedStatus);
                $em->persist($archivedStatus);
                $uow->computeChangeSet($projectParticipationStatusClassMetadata, $archivedStatus);
                $uow->recomputeSingleEntityChangeSet($projectParticipationClassMetadata, $participation);
            }
        }
    }
}
