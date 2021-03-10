<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Unilend\Core\Entity\Constant\AbstractEnum;
use Unilend\CreditGuaranty\Entity\{Constant\EligibilityFieldAlias, Program, ProgramChoiceOption};
use Unilend\CreditGuaranty\Repository\EligibilityCriteriaRepository;

class ProgramCreatedListener
{
    private EligibilityCriteriaRepository $eligibilityCriteriaRepository;

    /**
     * @param EligibilityCriteriaRepository $eligibilityCriteriaRepository
     */
    public function __construct(EligibilityCriteriaRepository $eligibilityCriteriaRepository)
    {
        $this->eligibilityCriteriaRepository = $eligibilityCriteriaRepository;
    }

    /**
     * @param OnFlushEventArgs $args
     *
     * @throws ORMException
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $classMetadata = $em->getClassMetadata(ProgramChoiceOption::class);

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (false === $entity instanceof Program) {
                continue;
            }
            //Auto-create the ProgramChoiceOptions with pre-defined list
            $programChoiceOptions   = [];
            // Get all criteria, because we create only the choice options for the field defined in this list.
            $allEligibilityCriteria = $this->eligibilityCriteriaRepository->findAll();
            $preDefinedLists        = EligibilityFieldAlias::getPredefinedListFields();
            foreach ($allEligibilityCriteria as $eligibilityCriteria) {
                $fieldAlias = $eligibilityCriteria->getFieldAlias();
                if (false === array_key_exists($fieldAlias, $preDefinedLists)) {
                    continue;
                }

                $constantClass = $preDefinedLists[$fieldAlias];
                if (is_subclass_of($constantClass, AbstractEnum::class)) {
                    foreach ($constantClass::getConstList() as $option) {
                        $programChoiceOptions[] = new ProgramChoiceOption($entity, $option, $fieldAlias);
                    }
                }
            }

            foreach ($programChoiceOptions as $programChoiceOption) {
                $em->persist($programChoiceOption);
                $uow->computeChangeSet($classMetadata, $programChoiceOption);
            }
        }
    }
}
