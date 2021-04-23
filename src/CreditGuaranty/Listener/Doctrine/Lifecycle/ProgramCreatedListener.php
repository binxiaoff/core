<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Repository\FieldRepository;

class ProgramCreatedListener
{
    private FieldRepository $fieldRepository;

    public function __construct(FieldRepository $fieldRepository)
    {
        $this->fieldRepository = $fieldRepository;
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em            = $args->getEntityManager();
        $uow           = $em->getUnitOfWork();
        $classMetadata = $em->getClassMetadata(Program::class);

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (false === $entity instanceof Program) {
                continue;
            }
            //Auto-create the ProgramChoiceOptions with pre-defined list
            //Get all "list" type criteria, because we create only the choice options for the field defined in this list.
            $listField = $this->fieldRepository->findBy(['type' => Field::TYPE_LIST]);
            foreach ($listField as $field) {
                if (null === $field->getPredefinedItems()) {
                    continue;
                }

                foreach ($field->getPredefinedItems() as $option) {
                    $entity->addProgramChoiceOption(new ProgramChoiceOption($entity, $option, $field));
                    $uow->computeChangeSet($classMetadata, $entity);
                }
            }
        }
    }
}
