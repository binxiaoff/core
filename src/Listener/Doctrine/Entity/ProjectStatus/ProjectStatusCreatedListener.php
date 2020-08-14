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
use Unilend\Entity\ProjectParticipation;
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
    public function transferInvitationReply(ProjectStatus $projectStatus, LifecycleEventArgs $args)
    {
        if (ProjectStatus::STATUS_ALLOCATION !== $projectStatus->getStatus()) {
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

    /**
     * @param ProjectStatus      $projectStatus
     * @param LifecycleEventArgs $args
     *
     * @throws Exception
     */
    public function acceptArrangerCommittee(ProjectStatus $projectStatus, LifecycleEventArgs $args)
    {
        if (ProjectStatus::STATUS_SYNDICATION_FINISHED !== $projectStatus->getStatus()) {
            return;
        }

        $user = $this->security->getUser();

        if (false === $user instanceof Clients) {
            return;
        }

        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return;
        }

        $participation = $projectStatus->getProject()->getArrangerProjectParticipation();
        $currentStatus = $participation->getCurrentStatus();
        if (null === $currentStatus || $currentStatus->getStatus() === ProjectParticipationStatus::STATUS_CREATED) {
            $em = $args->getEntityManager();

            $nextStatus = new ProjectParticipationStatus($participation, ProjectParticipationStatus::STATUS_COMMITTEE_ACCEPTED, $staff);
            $participation->setCurrentStatus($nextStatus);

            $em->persist($participation);
            $em->flush();
        }
    }
}
