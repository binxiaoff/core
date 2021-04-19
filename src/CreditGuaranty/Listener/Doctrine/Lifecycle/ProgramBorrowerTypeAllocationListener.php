<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\{EntityManager, Event\OnFlushEventArgs, Mapping\ClassMetadata, ORMException, UnitOfWork};
use Unilend\CreditGuaranty\Entity\{Constant\FieldAlias, ProgramBorrowerTypeAllocation, ProgramEligibilityConfiguration};
use Unilend\CreditGuaranty\Repository\{FieldRepository, ProgramEligibilityRepository};

class ProgramBorrowerTypeAllocationListener
{
    private ProgramEligibilityRepository $programEligibilityRepository;
    /** @var FieldRepository */
    private FieldRepository $fieldRepository;

    /**
     * @param ProgramEligibilityRepository $programEligibilityRepository
     * @param FieldRepository              $fieldRepository
     */
    public function __construct(ProgramEligibilityRepository $programEligibilityRepository, FieldRepository $fieldRepository)
    {
        $this->programEligibilityRepository = $programEligibilityRepository;
        $this->fieldRepository              = $fieldRepository;
    }

    /**
     * @param OnFlushEventArgs $args
     *
     * @throws ORMException
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getEntityManager();
        $unitOfWork    = $entityManager->getUnitOfWork();

        $classMetadata = $entityManager->getClassMetadata(ProgramEligibilityConfiguration::class);
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if (false === $entity instanceof ProgramBorrowerTypeAllocation) {
                continue;
            }
            $this->onCreate($entity, $entityManager, $unitOfWork, $classMetadata);
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if (false === $entity instanceof ProgramBorrowerTypeAllocation) {
                continue;
            }
            $this->onDelete($entity, $entityManager);
        }
    }

    /**
     * @param ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation
     * @param EntityManager                 $entityManager
     * @param UnitOfWork                    $unitOfWork
     * @param ClassMetadata                 $classMetadata
     *
     * @throws ORMException
     */
    private function onCreate(
        ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation,
        EntityManager $entityManager,
        UnitOfWork $unitOfWork,
        ClassMetadata $classMetadata
    ): void {
        $field = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::BORROWER_TYPE]);
        $programEligibility  = $this->programEligibilityRepository->findOneBy([
            'program' => $programBorrowerTypeAllocation->getProgram(),
            'field'   => $field,
        ]);

        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, $programBorrowerTypeAllocation->getProgramChoiceOption(), null, true);
        $entityManager->persist($programEligibilityConfiguration);
        $unitOfWork->computeChangeSet($classMetadata, $programEligibilityConfiguration);
    }

    /**
     * @param ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation
     * @param EntityManager                 $entityManager
     *
     * @throws ORMException
     */
    private function onDelete(ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation, EntityManager $entityManager): void
    {
        $entityManager->remove($programBorrowerTypeAllocation->getProgramChoiceOption());
    }
}
