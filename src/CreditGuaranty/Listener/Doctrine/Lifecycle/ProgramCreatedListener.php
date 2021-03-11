<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Unilend\CreditGuaranty\Entity\{ConstantList\EligibilityCriteria, Program, ProgramChoiceOption};
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
            //Get all "list" type criteria, because we create only the choice options for the field defined in this list.
            $listEligibilityCriteria = $this->eligibilityCriteriaRepository->findBy(['type' => EligibilityCriteria::TYPE_LIST]);
            foreach ($listEligibilityCriteria as $eligibilityCriteria) {
                if (null === $eligibilityCriteria->getPredefinedItems()) {
                    continue;
                }

                foreach ($eligibilityCriteria->getPredefinedItems() as $option) {
                    $programChoiceOption = new ProgramChoiceOption($entity, $option, $eligibilityCriteria);
                    $em->persist($programChoiceOption);
                    $uow->computeChangeSet($classMetadata, $programChoiceOption);
                }
            }
        }
    }
}
