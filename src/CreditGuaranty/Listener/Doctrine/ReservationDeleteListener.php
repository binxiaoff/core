<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Listener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\Entity\Staff;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Entity\ReservationStatus;

class ReservationDeleteListener
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @throws ORMException
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $token = $this->tokenStorage->getToken();
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        $em                             = $args->getEntityManager();
        $uow                            = $em->getUnitOfWork();
        $reservationClassMetadata       = $em->getClassMetadata(Reservation::class);
        $reservationStatusClassMetadata = $em->getClassMetadata(ReservationStatus::class);

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (false === ($entity instanceof Reservation)) {
                continue;
            }

            if ($staff instanceof Staff && false === $entity->isArchived()) {
                $entity->archive($staff);
            }

            $em->persist($entity);

            $uow->computeChangeSet($reservationClassMetadata, $entity);
            $uow->computeChangeSet($reservationStatusClassMetadata, $entity->getCurrentStatus());
            $uow->scheduleExtraUpdate($entity, $uow->getEntityChangeSet($entity));
        }
    }
}
