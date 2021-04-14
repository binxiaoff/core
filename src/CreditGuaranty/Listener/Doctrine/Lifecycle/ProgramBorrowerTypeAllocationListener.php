<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Unilend\Core\Listener\Doctrine\Lifecycle\AbstractOnFlushMemoryListener;
use Unilend\CreditGuaranty\Entity\Constant\FieldAlias;
use Unilend\CreditGuaranty\Entity\ProgramBorrowerTypeAllocation;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use Unilend\CreditGuaranty\Repository\FieldRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityConfigurationRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityRepository;

/**
 * When we create a ProgramBorrowerTypeAllocation, we create also automatically the eligibility along with its configuration
 * When we delete a ProgramBorrowerTypeAllocation, we delete the option and all its usages if it's not used, otherwise, we delete only the eligibility.
 */
class ProgramBorrowerTypeAllocationListener extends AbstractOnFlushMemoryListener
{
    protected const SUPPORTED_ENTITY_CLASSES = [ProgramBorrowerTypeAllocation::class];

    private ProgramEligibilityRepository                   $programEligibilityRepository;
    private FieldRepository                                $fieldRepository;
    private ProgramEligibilityConfigurationRepository      $programEligibilityConfigurationRepository;

    /**
     * @param ProgramEligibilityRepository              $programEligibilityRepository
     * @param FieldRepository                           $fieldRepository
     * @param ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository
     */
    public function __construct(
        ProgramEligibilityRepository $programEligibilityRepository,
        FieldRepository $fieldRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository
    ) {
        $this->programEligibilityRepository              = $programEligibilityRepository;
        $this->fieldRepository                           = $fieldRepository;
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
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
            if (false === $entity instanceof ProgramBorrowerTypeAllocation) {
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
            if (false === $entity instanceof ProgramBorrowerTypeAllocation) {
                continue;
            }
            $this->onCreate($entity, $entityManager);
        }
    }

    /**
     * @param ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation
     * @param EntityManager                 $entityManager
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function onCreate(ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation, EntityManager $entityManager): void
    {
        $field              = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::BORROWER_TYPE]);
        $programEligibility = $this->programEligibilityRepository->findOneBy([
            'program' => $programBorrowerTypeAllocation->getProgram(),
            'field'   => $field,
        ]);

        if (null === $programEligibility) {
            // The ProgramEligibilityConfiguration will be created automatically (see ProgramEligibilityCreatedListener)
            $programEligibility = new ProgramEligibility($programBorrowerTypeAllocation->getProgram(), $field);
            $entityManager->persist($programEligibility);
        }

        $programEligibilityConfiguration = $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility,
            'programChoiceOption' => $programBorrowerTypeAllocation->getProgramChoiceOption(),
        ]);

        if (null === $programEligibilityConfiguration) {
            $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, $programBorrowerTypeAllocation->getProgramChoiceOption(), null, true);
            $entityManager->persist($programEligibilityConfiguration);
        }
        $programEligibilityConfiguration->setEligible(true);

        $entityManager->flush();
    }

    /**
     * @param ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation
     * @param EntityManager                 $entityManager
     *
     * @throws ORMException
     */
    private function onDelete(ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation, EntityManager $entityManager): void
    {
        // todo: or if choice option is not used by the projects
        if ($programBorrowerTypeAllocation->getProgram()->isInDraft()) {
            // ProgramEligibility will also be (cascade) removed.
            $entityManager->remove($programBorrowerTypeAllocation->getProgramChoiceOption());
        } else {
            $field              = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::BORROWER_TYPE]);
            $programEligibility = $this->programEligibilityRepository->findOneBy([
                'program' => $programBorrowerTypeAllocation->getProgram(),
                'field'   => $field,
            ]);

            if ($programEligibility) {
                $programEligibilityConfiguration = $this->programEligibilityConfigurationRepository->findOneBy([
                    'programEligibility'  => $programEligibility,
                    'programChoiceOption' => $programBorrowerTypeAllocation->getProgramChoiceOption(),
                ]);

                if ($programEligibilityConfiguration) {
                    $entityManager->remove($programEligibilityConfiguration);
                }
            }
        }
    }
}
