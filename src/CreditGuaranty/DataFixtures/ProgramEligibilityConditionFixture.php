<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Faker\Provider\Miscellaneous;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityCondition;
use Unilend\CreditGuaranty\Repository\FieldRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityConfigurationRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityRepository;

class ProgramEligibilityConditionFixture extends AbstractFixtures implements DependentFixtureInterface
{
    private const CHANCE_OF_HAVING_CONDITION = 30;

    private FieldRepository $fieldRepository;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;
    private ProgramEligibilityRepository $programEligibilityRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        FieldRepository $fieldRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository,
        ProgramEligibilityRepository $programEligibilityRepository
    ) {
        parent::__construct($tokenStorage);
        $this->fieldRepository                           = $fieldRepository;
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
        $this->programEligibilityRepository              = $programEligibilityRepository;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            ProgramEligibilityConfigurationFixtures::class,
        ];
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Collection|Field[] $comparableFields */
        $comparableFields = $this->fieldRepository->findBy(['comparable' => true]);
        $comparableFields = array_filter($comparableFields, static fn (Field $field) => false === empty($field->getReservationPropertyName()));

        $operations = ProgramEligibilityCondition::getAvailableOperations();
        $valueTypes = ProgramEligibilityCondition::getAvailableValueType();

        /** @var Program $program */
        foreach ($this->getReferences(ProgramFixtures::ALL_PROGRAMS) as $program) {
            $programEligibilities             = $this->programEligibilityRepository->findBy(['program' => $program]);
            $programEligibilityConfigurations = $this->programEligibilityConfigurationRepository->findBy(['programEligibility' => $programEligibilities]);

            foreach ($programEligibilityConfigurations as $programEligibilityConfiguration) {
                if (false === Miscellaneous::boolean(self::CHANCE_OF_HAVING_CONDITION)) {
                    continue;
                }

                for ($i = 0; $i <= random_int(1, count($comparableFields) - 1); ++$i) {
                    $leftOperand = $comparableFields[$i];
                    $rightFields = $comparableFields;
                    shuffle($rightFields);
                    $valueType = $valueTypes[array_rand($valueTypes)];

                    if (ProgramEligibilityCondition::VALUE_TYPE_RATE === $valueType) {
                        foreach ($rightFields as $rightOperand) {
                            if ($leftOperand === $rightOperand || $rightOperand->getUnit() !== $leftOperand->getUnit()) {
                                continue;
                            }

                            $programEligibilityCondition = new ProgramEligibilityCondition(
                                $programEligibilityConfiguration,
                                $leftOperand,
                                $rightOperand,
                                $operations[array_rand($operations)],
                                $valueType,
                                (string) (mt_rand() / mt_getrandmax())
                            );

                            $manager->persist($programEligibilityCondition);

                            break 2;
                        }

                        // need to continue here to break in case there is no condition for rate value type
                        continue;
                    }

                    $programEligibilityCondition = new ProgramEligibilityCondition(
                        $programEligibilityConfiguration,
                        $leftOperand,
                        null,
                        $operations[array_rand($operations)],
                        $valueType,
                        (string) random_int(1, 9999)
                    );

                    $manager->persist($programEligibilityCondition);
                }
            }
        }

        $manager->flush();
    }
}
