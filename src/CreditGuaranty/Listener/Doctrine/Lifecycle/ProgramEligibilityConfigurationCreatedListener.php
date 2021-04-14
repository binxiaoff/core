<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\ORMException;
use Unilend\Core\Listener\Doctrine\Lifecycle\AbstractOnFlushMemoryListener;
use Unilend\CreditGuaranty\Entity\Constant\FieldAlias;
use Unilend\CreditGuaranty\Entity\ProgramBorrowerTypeAllocation;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use Unilend\CreditGuaranty\Repository\ProgramBorrowerTypeAllocationRepository;

class ProgramEligibilityConfigurationCreatedListener extends AbstractOnFlushMemoryListener
{
    protected const SUPPORTED_ENTITY_CLASSES = [ProgramEligibilityConfiguration::class];

    private ProgramBorrowerTypeAllocationRepository $programBorrowerTypeAllocationRepository;

    /**
     * @param ProgramBorrowerTypeAllocationRepository $programBorrowerTypeAllocationRepository
     */
    public function __construct(ProgramBorrowerTypeAllocationRepository $programBorrowerTypeAllocationRepository)
    {
        $this->programBorrowerTypeAllocationRepository = $programBorrowerTypeAllocationRepository;
    }

    /**
     * @param OnFlushEventArgs $args
     *
     * @throws ORMException
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        parent::onFlush($args);
        $entityManager = $args->getEntityManager();
        foreach ($entityManager->getUnitOfWork()->getScheduledEntityDeletions() as $entity) {
            if (false === $entity instanceof ProgramEligibilityConfiguration) {
                continue;
            }
            $this->onDelete($entity, $entityManager);
        }
    }

    /**
     * @param PostFlushEventArgs $args
     *
     * @throws ORMException
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        $entityManager = $args->getEntityManager();
        while ($entity = $this->shiftInsertedEntity()) {
            if (false === $entity instanceof ProgramEligibilityConfiguration) {
                continue;
            }
            $this->onCreate($entity, $entityManager);
        }
    }

    /**
     * @param ProgramEligibilityConfiguration $programEligibilityConfiguration
     * @param EntityManager                   $entityManager
     *
     * @throws ORMException
     */
    private function onCreate(
        ProgramEligibilityConfiguration $programEligibilityConfiguration,
        EntityManager $entityManager
    ): void {
        if (FieldAlias::BORROWER_TYPE === $programEligibilityConfiguration->getProgramEligibility()->getField()->getFieldAlias()) {
            $programBorrowerTypeAllocation = $this->programBorrowerTypeAllocationRepository->findOneBy([
                'program'             => $programEligibilityConfiguration->getProgramEligibility()->getProgram(),
                'programChoiceOption' => $programEligibilityConfiguration->getProgramChoiceOption(),
            ]);
            if (null === $programBorrowerTypeAllocation) {
                $programBorrowerTypeAllocation = new ProgramBorrowerTypeAllocation(
                    $programEligibilityConfiguration->getProgramEligibility()->getProgram(),
                    $programEligibilityConfiguration->getProgramChoiceOption(),
                    '1'
                );
                $entityManager->persist($programBorrowerTypeAllocation);
                $entityManager->flush();
            }
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
        // todo: or if choice option is not used by the projects
        if ($programEligibilityConfiguration->getProgramEligibility()->getProgram()->isInDraft()) {
            // ProgramBorrowerTypeAllocation will also be (cascade) removed.
            $entityManager->remove($programEligibilityConfiguration->getProgramChoiceOption());
        } else {
            $programBorrowerTypeAllocation = $this->programBorrowerTypeAllocationRepository->findOneBy([
                'program'             => $programEligibilityConfiguration->getProgramEligibility()->getProgram(),
                'programChoiceOption' => $programEligibilityConfiguration->getProgramChoiceOption(),
            ]);
            if ($programBorrowerTypeAllocation) {
                $entityManager->remove($programBorrowerTypeAllocation);
            }
        }
    }
}
