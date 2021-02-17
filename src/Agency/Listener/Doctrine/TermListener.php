<?php

declare(strict_types=1);

namespace Unilend\Agency\Listener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Exception;
use Unilend\Agency\Entity\Term;

class TermListener
{
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

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof Term) {
                $em->persist($entity);

                $entity->archive();

                $uow->computeChangeSet($em->getClassMetadata(Term::class), $entity);
                $uow->scheduleExtraUpdate($entity, $uow->getEntityChangeSet($uow));
            }
        }
    }
}
