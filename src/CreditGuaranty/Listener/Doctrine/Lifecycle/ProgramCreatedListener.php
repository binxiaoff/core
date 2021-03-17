<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Unilend\CreditGuaranty\Entity\{FieldConfiguration, Program, ProgramChoiceOption};
use Unilend\CreditGuaranty\Repository\FieldConfigurationRepository;

class ProgramCreatedListener
{
    private FieldConfigurationRepository $fieldConfigurationRepository;

    /**
     * @param FieldConfigurationRepository $fieldConfigurationRepository
     */
    public function __construct(FieldConfigurationRepository $fieldConfigurationRepository)
    {
        $this->fieldConfigurationRepository = $fieldConfigurationRepository;
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
            $listFieldConfiguration = $this->fieldConfigurationRepository->findBy(['type' => FieldConfiguration::TYPE_LIST]);
            foreach ($listFieldConfiguration as $fieldConfiguration) {
                if (null === $fieldConfiguration->getPredefinedItems()) {
                    continue;
                }

                foreach ($fieldConfiguration->getPredefinedItems() as $option) {
                    $programChoiceOption = new ProgramChoiceOption($entity, $option, $fieldConfiguration);
                    $em->persist($programChoiceOption);
                    $uow->computeChangeSet($classMetadata, $programChoiceOption);
                }
            }
        }
    }
}
