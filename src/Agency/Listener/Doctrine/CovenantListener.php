<?php

declare(strict_types=1);

namespace Unilend\Agency\Listener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Exception;
use Unilend\Agency\Entity\Covenant;
use Unilend\Agency\Entity\Term;

class CovenantListener
{
    /**
     * Archive on delete published covenant.
     *
     * @throws ORMException
     * @throws Exception
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();

        $uow = $em->getUnitOfWork();

        $covenantClassMetadata = $em->getClassMetadata(Covenant::class);

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof Covenant && $entity->isPublished()) {
                $entity->archive();

                $em->persist($entity);

                $uow->computeChangeSet($covenantClassMetadata, $entity);
                $uow->scheduleExtraUpdate($entity, $uow->getEntityChangeSet($entity));
            }
        }
    }

    /**
     * Delete extraneous terms for archived term (create on publication but not yet started [startDate > now]).
     * Use postUpdate because archiving <b>update</b> the covenant.
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof Covenant && $entity->isArchived()) {
            $em = $args->getEntityManager();

            // Needed because the filter will add condition to the query otherwise
            $em->getFilters()->disable('term');

            // Not in a repository because it is a cleanup operation
            // It should not be available in code
            $query = $em->createQueryBuilder()
                ->delete(Term::class, 't')
                ->where('t.startDate > :now')
                ->andWhere('t.covenant = :covenant')
                ->setParameter('covenant', $entity)
                ->setParameter('now', new \DateTimeImmutable())
                ->getQuery()
            ;

            $query->execute();

            $em->getFilters()->enable('term');
        }
    }
}
