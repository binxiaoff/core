<?php

declare(strict_types=1);

namespace Unilend\Agency\Listener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Exception;
use Unilend\Agency\Entity\BorrowerMember;

class BorrowerMemberListener
{
    /**
     * Archive on delete borrower member published project.
     *
     * @throws ORMException
     * @throws Exception
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();

        $uow = $em->getUnitOfWork();

        $classMetadata = $em->getClassMetadata(BorrowerMember::class);

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof BorrowerMember && $entity->getProject()->isPublished()) {
                $entity->archive();

                $em->persist($entity);

                $uow->computeChangeSet($classMetadata, $entity);
            }
        }
    }
}
