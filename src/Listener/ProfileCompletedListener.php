<?php

declare(strict_types=1);

namespace Unilend\Listener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Unilend\Entity\{ClientStatus, Clients};

class ProfileCompletedListener
{
    /**
     * @param OnFlushEventArgs $eventArgs
     *
     * @throws ORMException
     */
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $em  = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        $clientStatusMetadata = $em->getClassMetadata(ClientStatus::class);

        foreach (array_merge($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates()) as $client) {
            if (
                ($client instanceof Clients)
                && $this->isProfileComplete($client)
                && $client->getCurrentStatus()->getStatus() < ClientStatus::STATUS_CREATED
            ) {
                $client->setCurrentStatus(ClientStatus::STATUS_CREATED);
                $newStatus = $client->getCurrentStatus();
                $em->persist($newStatus);
                $uow->computeChangeSet($clientStatusMetadata, $newStatus);
            }
        }
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    private function isProfileComplete(Clients $client): bool
    {
        return $client->getFirstName() && $client->getLastName() && $client->getPassword();
    }
}
