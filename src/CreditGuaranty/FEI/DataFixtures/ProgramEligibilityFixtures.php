<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProgramEligibilityFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private FieldRepository $fieldRepository;

    public function __construct(TokenStorageInterface $tokenStorage, FieldRepository $fieldRepository)
    {
        parent::__construct($tokenStorage);
        $this->fieldRepository = $fieldRepository;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            ProgramChoiceOptionFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->loadData() as $data) {
            /** @var Program $program */
            foreach ($data['programs'] as $program) {
                /** @var Field $field */
                foreach ($data['fields'] as $field) {
                    $programEligibility = new ProgramEligibility($program, $field);
                    $manager->persist($programEligibility);
                }

                $manager->flush();
            }
        }
    }

    /**
     * These data must be the same as ProgramChoiceOptionFixtures.
     */
    private function loadData(): iterable
    {
        yield 'programs with all configured fields' => [
            'programs' => $this->getReferences([
                ProgramFixtures::PROGRAM_AGRICULTURE_COMMERCIALIZED,
                ProgramFixtures::PROGRAM_CORPORATE_PAUSED,
                ProgramFixtures::PROGRAM_CORPORATE_ARCHIVED,
            ]),
            'fields' => $this->fieldRepository->findBy(['tag' => Field::TAG_ELIGIBILITY]),
        ];
        yield 'programs with some configured fields' => [
            'programs' => $this->getReferences([
                ProgramFixtures::PROGRAM_AGRICULTURE_PAUSED,
                ProgramFixtures::PROGRAM_AGRICULTURE_ARCHIVED,
                ProgramFixtures::PROGRAM_CORPORATE_COMMERCIALIZED,
            ]),
            'fields' => $this->fieldRepository->findBy([
                'fieldAlias' => [
                    FieldAlias::AGRICULTURAL_BRANCH,
                    FieldAlias::AID_INTENSITY,
                    FieldAlias::BENEFICIARY_NAME,
                    FieldAlias::BENEFITING_PROFIT_TRANSFER,
                    FieldAlias::COMPANY_NAME,
                    FieldAlias::ELIGIBLE_FEI_CREDIT,
                    FieldAlias::FINANCING_OBJECT_TYPE,
                    FieldAlias::LEGAL_FORM,
                    FieldAlias::LOAN_DURATION,
                    FieldAlias::LOAN_MONEY,
                    FieldAlias::LOAN_NAF_CODE,
                    FieldAlias::PROJECT_GRANT,
                    FieldAlias::RECEIVING_GRANT,
                    FieldAlias::SUPPORTING_GENERATIONS_RENEWAL,
                    FieldAlias::TARGET_TYPE,
                    FieldAlias::TOTAL_FEI_CREDIT,
                    FieldAlias::YOUNG_FARMER,
                ],
            ]),
        ];
    }
}
