<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\Core\Entity\Constant\CAInternalRating;
use Unilend\Core\Entity\Constant\CAInternalRetailRating;
use Unilend\Core\Entity\Constant\CARatingType;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramGradeAllocation;

class ProgramGradeAllocationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $programReferenceNames = [
            ProgramFixtures::REFERENCE_CANCELLED,
            ProgramFixtures::REFERENCE_COMMERCIALIZED,
            ProgramFixtures::REFERENCE_DRAFT,
            ProgramFixtures::REFERENCE_PAUSED,
        ];

        foreach ($programReferenceNames as $programReferenceName) {
            $program = $this->getReference($programReferenceName);

            $this->buildProgramGradeAllocationFixtures($program, $manager);
        }
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            ProgramFixtures::class,
        ];
    }

    /**
     * @param Program       $program
     * @param ObjectManager $manager
     *
     * @throws Exception
     */
    private function buildProgramGradeAllocationFixtures(Program $program, ObjectManager $manager)
    {
        $maxAllocationRates = ['0.10', '0.20', '0.30', '0.40', '0.50', '0.60', '0.70', '0.80', '0.90', '1'];
        $grades             = CARatingType::CA_INTERNAL_RETAIL_RATING === $program->getRatingType() ? CAInternalRetailRating::getConstList() : CAInternalRating::getConstList();
        $nbGrades           = count($grades);
        shuffle($grades);

        for ($i = 0; $i <= random_int(1, $nbGrades - 1); $i++) {
            $programGradeAllocation = new ProgramGradeAllocation($program, array_values($grades)[$i], $maxAllocationRates[array_rand($maxAllocationRates)]);
            $manager->persist($programGradeAllocation);
        }
    }
}
