<?php

declare(strict_types=1);

namespace Unilend\Agency\Listener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Exception;
use Unilend\Agency\Entity\Term;
use Unilend\Agency\Entity\TermHistory;
use Unilend\Agency\Repository\TermHistoryRepository;

class TermListener
{
    private TermHistoryRepository $termHistoryRepository;

    /**
     * @param TermHistoryRepository $termHistoryRepository
     */
    public function __construct(TermHistoryRepository $termHistoryRepository)
    {
        $this->termHistoryRepository = $termHistoryRepository;
    }

    /**
     * @param OnFlushEventArgs $args
     *
     * @throws ORMException
     * @throws Exception
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();

        $uow = $em->getUnitOfWork();

        $termClassMetadata = $em->getClassMetadata(Term::class);
        $termHistoryMetadata = $em->getClassMetadata(TermHistory::class);

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Term) {
                $changeSet = $uow->getEntityChangeSet($entity);

                $historyEntry = $this->termHistoryRepository->findLatestHistoryEntry($entity);

                if (null === $historyEntry || \array_key_exists('borrowerInput', $changeSet) || \array_key_exists('borrowerDocument', $changeSet)) {
                    $historyEntry = new TermHistory($entity);
                }

                $historyEntry->update($entity);
                $em->persist($historyEntry);

                $uow->computeChangeSet($termHistoryMetadata, $historyEntry);
                $uow->scheduleExtraUpdate($historyEntry, $uow->getEntityChangeSet($historyEntry));
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof Term) {
                $em->persist($entity);

                $entity->archive();

                $uow->computeChangeSet($termClassMetadata, $entity);
                $uow->scheduleExtraUpdate($entity, $uow->getEntityChangeSet($uow));
            }
        }
    }
}
