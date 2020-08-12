<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\ProjectStatus;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Unilend\Entity\Embeddable\Offer;
use Unilend\Entity\ProjectStatus;

class ProjectStatusCreatedListener
{
    /**
     * @param ProjectStatus      $projectStatus
     * @param LifecycleEventArgs $args
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function transferInvitationReply(ProjectStatus $projectStatus, LifecycleEventArgs $args)
    {
        if ($projectStatus->getStatus() !== ProjectStatus::STATUS_ALLOCATION) {
            return;
        }

        $project = $projectStatus->getProject();

        $statuses = $project->getStatuses();

        $previousStatus = $statuses->last();
        // Ensure to have the correct previous status (in case current status have been added to statuses array)
        // $previousStatus = $previousStatus === $projectStatus ? $statuses[$statuses->count() - 2] : $previousStatus;

        if (null === $previousStatus || $previousStatus->getStatus() !== ProjectStatus::STATUS_PARTICIPANT_REPLY) {
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
}
