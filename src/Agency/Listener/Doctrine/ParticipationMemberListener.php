<?php

declare(strict_types=1);

namespace Unilend\Agency\Listener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Exception;
use Unilend\Agency\Entity\Participation;
use Unilend\Agency\Entity\ParticipationMember;

class ParticipationMemberListener
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

        $classMetadata = $em->getClassMetadata(ParticipationMember::class);

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof ParticipationMember && $entity->getProject()->isPublished()) {
                $entity->archive();

                $em->persist($entity);

                $uow->computeChangeSet($classMetadata, $entity);
            }
        }
    }
}
