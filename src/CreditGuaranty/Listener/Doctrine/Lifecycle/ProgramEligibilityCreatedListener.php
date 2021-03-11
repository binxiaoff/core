<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Listener\Doctrine\Lifecycle;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Unilend\CreditGuaranty\Entity\{ConstantList\EligibilityCriteria, ProgramEligibility, ProgramEligibilityConfiguration};
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
                $configurations = [];
                $eligibilityCriteria = $entity->getEligibilityCriteria();
                // auto-configure the new-created eligibility
                switch ($eligibilityCriteria->getType()) {
                    // For the "other", the only reason that it's added to the program is to let the target field be required, thus we set always its eligible to true.
                    case EligibilityCriteria::TYPE_OTHER:
                        $configurations[] = new ProgramEligibilityConfiguration($entity, null, null, true);
                        break;
                    case EligibilityCriteria::TYPE_BOOL:
                        $configurations[] = new ProgramEligibilityConfiguration($entity, null, EligibilityCriteria::VALUE_BOOL_YES, false);
                        $configurations[] = new ProgramEligibilityConfiguration($entity, null, EligibilityCriteria::VALUE_BOOL_NO, false);
                        break;
                    case EligibilityCriteria::TYPE_LIST:
                        $options = $this->programChoiceOptionRepository->findBy(['program' => $entity->getProgram(), 'eligibilityCriteria' => $eligibilityCriteria]);
                        foreach ($options as $programChoiceOption) {
                            $configurations[] = new ProgramEligibilityConfiguration($entity, $programChoiceOption, null, false);
                        }
                        break;
                }
                foreach ($configurations as $programEligibilityConfiguration) {
                    $em->persist($programEligibilityConfiguration);
                    $uow->computeChangeSet($classMetadata, $programEligibilityConfiguration);
                }
            }
        }
    }
}
