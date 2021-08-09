<?php

declare(strict_types=1);

namespace KLS\Agency\Listener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Exception;
use KLS\Agency\Entity\Participation;

class ParticipationListener
{
    /**
     * Archive on delete participation for published project.
     *
     * @throws ORMException
     * @throws Exception
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();

        $uow = $em->getUnitOfWork();

        $participationClassMetadata = $em->getClassMetadata(Participation::class);

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof Participation && $entity->getProject()->isPublished()) {
                $entity->archive();

                $em->persist($entity);

                $uow->computeChangeSet($participationClassMetadata, $entity);
            }
        }
    }
}
