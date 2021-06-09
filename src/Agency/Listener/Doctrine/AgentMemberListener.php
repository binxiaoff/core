<?php

declare(strict_types=1);

namespace Unilend\Agency\Listener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Exception;
use Unilend\Agency\Entity\AgentMember;

class AgentMemberListener
{
    /**
     * Archive on delete agent member published project.
     *
     * @throws ORMException
     * @throws Exception
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();

        $uow = $em->getUnitOfWork();

        $classMetadata = $em->getClassMetadata(AgentMember::class);

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof AgentMember && $entity->getProject()->isPublished()) {
                $entity->archive();

                $em->persist($entity);

                $uow->computeChangeSet($classMetadata, $entity);
            }
        }
    }
}
