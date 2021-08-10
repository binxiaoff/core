<?php

declare(strict_types=1);

namespace KLS\Agency\Listener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Exception;
use KLS\Agency\Entity\Term;
use KLS\Agency\Entity\TermHistory;

class TermListener
{
    /**
     * @throws ORMException
     * @throws Exception
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();

        $uow = $em->getUnitOfWork();

        $termClassMetadata   = $em->getClassMetadata(Term::class);
        $termHistoryMetadata = $em->getClassMetadata(TermHistory::class);

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Term) {
                $historyEntry = new TermHistory($entity);

                $em->persist($historyEntry);

                $uow->computeChangeSet($termHistoryMetadata, $historyEntry);
                $uow->scheduleExtraUpdate($historyEntry, $uow->getEntityChangeSet($historyEntry));
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof Term) {
                $entity->archive();

                $em->persist($entity);

                $uow->computeChangeSet($termClassMetadata, $entity);
                $uow->scheduleExtraUpdate($entity, $uow->getEntityChangeSet($entity));
            }
        }
    }
}
