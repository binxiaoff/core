<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\ProjectStatus;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Exception;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Clients;
use Unilend\Entity\Embeddable\Offer;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationStatus;
use Unilend\Entity\ProjectParticipationTranche;
use Unilend\Entity\ProjectStatus;

class ProjectStatusListener
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
     * @param OnFlushEventArgs $args
     *
     * @throws Exception
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
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
     * @param Project       $project
     * @param EntityManager $em
     *
     * @throws Exception
     */
    private function onStatusChange(Project $project, EntityManager $em)
    {
        $currentStatus = $project->getCurrentStatus();

        if (ProjectStatus::STATUS_ALLOCATION === $currentStatus->getStatus()) {
            $this->transferInvitationReply($project, $em);
        }
    }

    /**
     * @param Project       $project
     * @param EntityManager $em
     *
     * @throws Exception
     */
    private function transferInvitationReply(Project $project, EntityManager $em)
    {
        $statuses = $project->getStatuses();

        // This works because we do not add currentStatus to the statuses list
        $previousStatus = $statuses->last();

        // Ensure to have the correct previous status (in case current status have been added to statuses array)
        // $previousStatus = $previousStatus === $projectStatus ? $statuses[$statuses->count() - 2] : $previousStatus;

        if (false === $previousStatus || $previousStatus->getStatus() !== ProjectStatus::STATUS_PARTICIPANT_REPLY) {
            return;
        }

        $projectParticipations = $project->getProjectParticipations();
        $uow = $em->getUnitOfWork();


        foreach ($projectParticipations as $projectParticipation) {
            foreach ($projectParticipation->getProjectParticipationTranches() as $projectParticipationTranche) {
                $invitationReply = $projectParticipationTranche->getInvitationReply();
                $allocationOffer = $projectParticipationTranche->getAllocation();

                if ($invitationReply->isValid() && false === $allocationOffer->isValid()) {
                    $projectParticipationTranche->setAllocation(new Offer($invitationReply->getMoney()));
                    $uow->computeChangeSet($em->getClassMetadata(ProjectParticipationTranche::class), $projectParticipationTranche);
                }
            }
        }
    }
}
