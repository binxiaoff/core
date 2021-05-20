<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Listener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\Entity\Staff;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramStatus;

class ProgramDeleteListener
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

        $em                         = $args->getEntityManager();
        $uow                        = $em->getUnitOfWork();
        $programClassMetadata       = $em->getClassMetadata(Program::class);
        $programStatusClassMetadata = $em->getClassMetadata(ProgramStatus::class);

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (false === $entity instanceof Program) {
                continue;
            }

            if ($entity->isInDraft()) {
                continue;
            }

            if ($staff instanceof Staff && false === $entity->isArchived()) {
                $entity->archive($staff);
            }

            $em->persist($entity);

            $uow->computeChangeSet($programClassMetadata, $entity);
            $uow->computeChangeSet($programStatusClassMetadata, $entity->getCurrentStatus());
            $uow->scheduleExtraUpdate($entity, $uow->getEntityChangeSet($entity));
        }
    }
}
