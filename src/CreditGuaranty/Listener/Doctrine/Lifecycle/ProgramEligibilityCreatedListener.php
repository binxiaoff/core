<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\ORMException;
use Unilend\Core\Listener\Doctrine\Lifecycle\AbstractOnFlushMemoryListener;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityConfigurationRepository;

/**
 * when creating a ProgramEligibility, we create automatically its ProgramEligibilityConfiguration.
 */
class ProgramEligibilityCreatedListener extends AbstractOnFlushMemoryListener
{
    protected const SUPPORTED_ENTITY_CLASSES = [ProgramEligibility::class];

    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;

    public function __construct(ProgramChoiceOptionRepository $programChoiceOptionRepository, ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository)
    {
        $this->programChoiceOptionRepository             = $programChoiceOptionRepository;
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
    }

    /**
     * @throws ORMException
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        $needFlush = false;
        $em        = $args->getEntityManager();
        while ($entity = $this->shiftInsertedEntity()) {
            if (false === $entity instanceof ProgramEligibility) {
                continue;
            }
            $field = $entity->getField();
            // auto-configure the new-created eligibility
            switch ($field->getType()) {
                // For the "other", the only reason that it's added to the program is to let the target field be required, thus we set always its eligible to true.
                case Field::TYPE_OTHER:
                    $configuration = $this->createConfiguration($entity, null, null);
                    $em->persist($configuration);

                    break;

                case Field::TYPE_BOOL:
                    $configuration = $this->createConfiguration($entity, null, Field::VALUE_BOOL_YES);
                    $em->persist($configuration);

                    $configuration = $this->createConfiguration($entity, null, Field::VALUE_BOOL_NO);
                    $em->persist($configuration);

                    break;

                case Field::TYPE_LIST:
                    $options = $this->programChoiceOptionRepository->findBy(['program' => $entity->getProgram(), 'field' => $field]);
                    foreach ($options as $programChoiceOption) {
                        $configuration = $this->createConfiguration($entity, $programChoiceOption, null);
                        $em->persist($configuration);
                    }

                    break;

                default:
                    throw new \UnexpectedValueException('The field type is not supported.');
            }
            $needFlush = true;
        }
        // important to check if we need flush, otherwise there will be a infinite loop.
        if ($needFlush) {
            $em->flush();
        }
    }

    private function createConfiguration(ProgramEligibility $programEligibility, ?ProgramChoiceOption $programChoiceOption, ?string $value): ProgramEligibilityConfiguration
    {
        $configuration = $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility,
            'programChoiceOption' => $programChoiceOption,
            'value'               => $value,
        ]);
        if (null === $configuration) {
            $configuration = new ProgramEligibilityConfiguration($programEligibility, $programChoiceOption, $value, true);
        }

        return $configuration;
    }
}
