<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\ProjectStatus;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Clients;
use Unilend\Entity\Embeddable\Offer;
use Unilend\Entity\ProjectParticipationStatus;
use Unilend\Entity\ProjectStatus;

class ProjectStatusCreatedListener
{
    /**
     * @var Security
     */
    private Security $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param ProjectStatus      $projectStatus
     * @param LifecycleEventArgs $args
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function postPersist(ProjectStatus $projectStatus, LifecycleEventArgs $args)
    {
        if (ProjectStatus::STATUS_ALLOCATION === $projectStatus->getStatus()) {
            $this->transferInvitationReply($projectStatus, $args);
        }

        if (ProjectStatus::STATUS_SYNDICATION_FINISHED === $projectStatus->getStatus()) {
            $this->acceptArrangerCommittee($projectStatus, $args);
        }
    }

    /**
     * @param ProjectStatus      $projectStatus
     * @param LifecycleEventArgs $args
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    private function transferInvitationReply(ProjectStatus $projectStatus, LifecycleEventArgs $args)
    {
        $project = $projectStatus->getProject();

        $statuses = $project->getStatuses();

        $previousStatus = $statuses->last();

        // Ensure to have the correct previous status (in case current status have been added to statuses array)
        // $previousStatus = $previousStatus === $projectStatus ? $statuses[$statuses->count() - 2] : $previousStatus;

        if (false === $previousStatus || $previousStatus->getStatus() !== ProjectStatus::STATUS_PARTICIPANT_REPLY) {
            return;
        }

        $projectParticipations = $project->getProjectParticipations();
        $em = $args->getEntityManager();

        foreach ($projectParticipations as $projectParticipation) {
            foreach ($projectParticipation->getProjectParticipationTranches() as $projectParticipationTranche) {
                $invitationReply = $projectParticipationTranche->getInvitationReply();
                $allocationOffer = $projectParticipationTranche->getAllocation();

                if ($invitationReply->isValid() && false === $allocationOffer->isValid()) {
                    $projectParticipationTranche->setAllocation(new Offer($invitationReply->getMoney()));
                    $em->persist($projectParticipationTranche);
                }
            }
        }

        $em->flush();
    }

    /**
     * @param ProjectStatus      $projectStatus
     * @param LifecycleEventArgs $args
     *
     * @throws Exception
     */
    private function acceptArrangerCommittee(ProjectStatus $projectStatus, LifecycleEventArgs $args)
    {
        $user = $this->security->getUser();

        if (false === $user instanceof Clients) {
            return;
        }

        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return;
        }

        $arrangerProjectParticipation = $projectStatus->getProject()->getArrangerProjectParticipation();
        $currentStatus = $arrangerProjectParticipation->getCurrentStatus();

        if (null === $currentStatus || $currentStatus->getStatus() === ProjectParticipationStatus::STATUS_CREATED) {
            $em = $args->getEntityManager();

            $nextStatus = new ProjectParticipationStatus($arrangerProjectParticipation, ProjectParticipationStatus::STATUS_COMMITTEE_ACCEPTED, $staff);
            $arrangerProjectParticipation->setCurrentStatus($nextStatus);

            $em->persist($arrangerProjectParticipation);
            $em->flush();
        }
    }
}
