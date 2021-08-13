<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\Entity\Constant\CAInternalRating;
use KLS\Core\Entity\Constant\CAInternalRetailRating;
use KLS\Core\Entity\Constant\CARatingType;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramGradeAllocation;

class ProgramGradeAllocationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Program $program */
        foreach ($this->getReferences(ProgramFixtures::ALL_PROGRAMS) as $program) {
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
     * @throws Exception
     */
    private function buildProgramGradeAllocationFixtures(Program $program, ObjectManager $manager): void
    {
        $maxAllocationRates = ['0.10', '0.20', '0.30', '0.40', '0.50', '0.60', '0.70', '0.80', '0.90', '1'];
        $grades             = CARatingType::CA_INTERNAL_RETAIL_RATING === $program->getRatingType() ? CAInternalRetailRating::getConstList() : CAInternalRating::getConstList();
        $nbGrades           = \count($grades);
        \shuffle($grades);

        for ($i = 0; $i <= \random_int(1, $nbGrades - 1); ++$i) {
            $programGradeAllocation = new ProgramGradeAllocation($program, \array_values($grades)[$i], $maxAllocationRates[\array_rand($maxAllocationRates)]);
            $manager->persist($programGradeAllocation);
        }
    }
}
