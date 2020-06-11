<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\ProjectParticipation;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\{Clients, ProjectParticipation, ProjectParticipationStatus};
use Unilend\Repository\ProjectParticipationStatusRepository;

class ProjectParticipationUpdatedListener
{
    /** @var Security */
    private $security;
    /** @var LoggerInterface */
    private $logger;
    /** @var ProjectParticipationStatusRepository */
    private $projectParticipationStatusRepository;

    /**
     * @param Security                             $security
     * @param LoggerInterface                      $logger
     * @param ProjectParticipationStatusRepository $projectParticipationStatusRepository
     */
    public function __construct(Security $security, LoggerInterface $logger, ProjectParticipationStatusRepository $projectParticipationStatusRepository)
    {
        $this->security                             = $security;
        $this->logger                               = $logger;
        $this->projectParticipationStatusRepository = $projectParticipationStatusRepository;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param PreUpdateEventArgs   $args
     *
     * @throws Exception
     */
    public function archiveOnRejectedByCommittee(ProjectParticipation $projectParticipation, PreUpdateEventArgs $args): void
    {
        $user  = $this->security->getUser();
        $staff = $user instanceof Clients ? $user->getCurrentStaff() : null;

        if (!$staff) {
            $this->logger->warning('Cannot get the current staff for client', [
                'id_client' => $user->getId(),
                'class'     => self::class,
            ]);

            return;
        }

        if (
            $args->hasChangedField(ProjectParticipation::FIELD_COMMITTEE_STATUS)
            && $args->getNewValue(ProjectParticipation::FIELD_COMMITTEE_STATUS)
            && ProjectParticipation::COMMITTEE_STATUS_REJECTED === $args->getNewValue(ProjectParticipation::FIELD_COMMITTEE_STATUS)
        ) {
            $projectParticipationStatus = new ProjectParticipationStatus($projectParticipation, ProjectParticipationStatus::STATUS_ARCHIVED_BY_PARTICIPANT, $staff);
            $this->projectParticipationStatusRepository->save($projectParticipationStatus);
        }
    }
}
