<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\Entity\Constant\MathOperator;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityCondition;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use KLS\Test\Core\DataFixtures\AbstractFixtures;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProgramEligibilityConditionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private ProgramEligibilityRepository $programEligibilityRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ProgramEligibilityRepository $programEligibilityRepository
    ) {
        parent::__construct($tokenStorage);
        $this->programEligibilityRepository = $programEligibilityRepository;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            ReservationFixtures::class,
            ProgramEligibilityConfigurationFixtures::class,
        ];
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Program $program */
        $program = $this->getReference(ProgramFixtures::REFERENCE_COMMERCIALIZED);
        /** @var Field $turnoverField */
        $turnoverField = $this->getReference('field-' . FieldAlias::TURNOVER);
        /** @var Field $totalAssetsField */
        $totalAssetsField = $this->getReference('field-' . FieldAlias::TOTAL_ASSETS);
        /** @var Field $totalFeiCreditField */
        $totalFeiCreditField = $this->getReference('field-' . FieldAlias::TOTAL_FEI_CREDIT);
        /** @var Field $creditExcludingFeiField */
        $creditExcludingFeiField = $this->getReference('field-' . FieldAlias::CREDIT_EXCLUDING_FEI);
        /** @var Field $loanDurationField */
        $loanDurationField = $this->getReference('field-' . FieldAlias::LOAN_DURATION);

        $turnoverEligibility = $this->programEligibilityRepository->findOneBy([
            'program' => $program,
            'field'   => $turnoverField,
        ]);
        $totalFeiCreditEligibility = $this->programEligibilityRepository->findOneBy([
            'program' => $program,
            'field'   => $totalFeiCreditField,
        ]);
        $loanDurationEligibility = $this->programEligibilityRepository->findOneBy([
            'program' => $program,
            'field'   => $loanDurationField,
        ]);

        foreach ($turnoverEligibility->getProgramEligibilityConfigurations() as $programEligibilityConfiguration) {
            $turnoverEligibilityCondition = (new ProgramEligibilityCondition(
                $programEligibilityConfiguration,
                $turnoverField,
                $totalAssetsField,
                MathOperator::INFERIOR,
                ProgramEligibilityCondition::VALUE_TYPE_RATE
            ))->setValue('0.2');
            $manager->persist($turnoverEligibilityCondition);
        }
        foreach ($totalFeiCreditEligibility->getProgramEligibilityConfigurations() as $programEligibilityConfiguration) {
            $totalFeiCreditEligibilityCondition = (new ProgramEligibilityCondition(
                $programEligibilityConfiguration,
                $totalFeiCreditField,
                $creditExcludingFeiField,
                MathOperator::SUPERIOR,
                ProgramEligibilityCondition::VALUE_TYPE_RATE
            ))->setValue('0.4');
            $manager->persist($totalFeiCreditEligibilityCondition);
        }
        foreach ($loanDurationEligibility->getProgramEligibilityConfigurations() as $programEligibilityConfiguration) {
            $loanDurationEligibilityCondition = (new ProgramEligibilityCondition(
                $programEligibilityConfiguration,
                $loanDurationField,
                null,
                MathOperator::SUPERIOR_OR_EQUAL,
                ProgramEligibilityCondition::VALUE_TYPE_VALUE
            ))->setValue('4');
            $manager->persist($loanDurationEligibilityCondition);
        }

        $manager->flush();
    }
}
