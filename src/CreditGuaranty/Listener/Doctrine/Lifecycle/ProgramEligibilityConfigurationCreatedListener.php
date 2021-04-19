<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\{EntityManager, Event\OnFlushEventArgs, Mapping\ClassMetadata, ORMException, UnitOfWork};
use Unilend\CreditGuaranty\Entity\{Constant\FieldAlias, ProgramBorrowerTypeAllocation, ProgramEligibilityConfiguration};

class ProgramEligibilityConfigurationCreatedListener
{
    /**
     * @param OnFlushEventArgs $args
     *
     * @throws ORMException
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getEntityManager();
        $unitOfWork    = $entityManager->getUnitOfWork();

        $classMetadata = $entityManager->getClassMetadata(ProgramBorrowerTypeAllocation::class);
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if (false === $entity instanceof ProgramEligibilityConfiguration) {
                continue;
            }
            $this->onCreate($entity, $entityManager, $unitOfWork, $classMetadata);
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if (false === $entity instanceof ProgramEligibilityConfiguration) {
                continue;
            }
            $this->onDelete($entity, $entityManager);
        }
    }

    /**
     * @param ProgramEligibilityConfiguration $programEligibilityConfiguration
     * @param EntityManager                   $entityManager
     * @param UnitOfWork                      $unitOfWork
     * @param ClassMetadata                   $classMetadata
     *
     * @throws ORMException
     */
    private function onCreate(
        ProgramEligibilityConfiguration $programEligibilityConfiguration,
        EntityManager $entityManager,
        UnitOfWork $unitOfWork,
        ClassMetadata $classMetadata
    ): void {
        if (FieldAlias::BORROWER_TYPE === $programEligibilityConfiguration->getProgramEligibility()->getField()->getFieldAlias()) {
            $programBorrowerTypeAllocation = new ProgramBorrowerTypeAllocation(
                $programEligibilityConfiguration->getProgramEligibility()->getProgram(),
                $programEligibilityConfiguration->getProgramChoiceOption(),
                '1'
            );
            $entityManager->persist($programBorrowerTypeAllocation);
            $unitOfWork->computeChangeSet($classMetadata, $programBorrowerTypeAllocation);
        }
    }

    /**
     * @param ProgramEligibilityConfiguration $programEligibilityConfiguration
     * @param EntityManager                   $entityManager
     *
     * @throws ORMException
     */
    private function onDelete(ProgramEligibilityConfiguration $programEligibilityConfiguration, EntityManager $entityManager): void
    {
        $entityManager->remove($programEligibilityConfiguration->getProgramChoiceOption());
    }
}
