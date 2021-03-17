<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Unilend\CreditGuaranty\Entity\{FieldConfiguration, ProgramEligibility, ProgramEligibilityConfiguration};
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;

class ProgramEligibilityCreatedListener
{
    /** @var ProgramChoiceOptionRepository */
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;

    /**
     * @param ProgramChoiceOptionRepository $programChoiceOptionRepository
     */
    public function __construct(ProgramChoiceOptionRepository $programChoiceOptionRepository)
    {
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
    }

    /**
     * @param OnFlushEventArgs $args
     *
     * @throws ORMException
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $classMetadata = $em->getClassMetadata(ProgramEligibilityConfiguration::class);
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof ProgramEligibility) {
                $fieldConfiguration = $entity->getFieldConfiguration();
                // auto-configure the new-created eligibility
                switch ($fieldConfiguration->getType()) {
                    // For the "other", the only reason that it's added to the program is to let the target field be required, thus we set always its eligible to true.
                    case FieldConfiguration::TYPE_OTHER:
                        $configuration = new ProgramEligibilityConfiguration($entity, null, null, true);
                        $em->persist($configuration);
                        $uow->computeChangeSet($classMetadata, $configuration);
                        break;
                    case FieldConfiguration::TYPE_BOOL:
                        $configuration = new ProgramEligibilityConfiguration($entity, null, FieldConfiguration::VALUE_BOOL_YES, false);
                        $em->persist($configuration);
                        $uow->computeChangeSet($classMetadata, $configuration);

                        $configuration = new ProgramEligibilityConfiguration($entity, null, FieldConfiguration::VALUE_BOOL_NO, false);
                        $em->persist($configuration);
                        $uow->computeChangeSet($classMetadata, $configuration);
                        break;
                    case FieldConfiguration::TYPE_LIST:
                        $options = $this->programChoiceOptionRepository->findBy(['program' => $entity->getProgram(), 'fieldConfiguration' => $fieldConfiguration]);
                        foreach ($options as $programChoiceOption) {
                            $configuration = new ProgramEligibilityConfiguration($entity, $programChoiceOption, null, false);
                            $em->persist($configuration);
                            $uow->computeChangeSet($classMetadata, $configuration);
                        }
                        break;
                    default:
                        throw new \UnexpectedValueException('The field type is not supported.');
                }
            }
        }
    }
}
