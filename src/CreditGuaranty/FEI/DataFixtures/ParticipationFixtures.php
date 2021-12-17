<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\DataFixtures\CompanyFixtures;
use KLS\Core\Entity\Company;
use KLS\Core\Traits\ConstantsAwareTrait;
use KLS\CreditGuaranty\FEI\Entity\Participation;
use KLS\CreditGuaranty\FEI\Entity\Program;

class ParticipationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    use ConstantsAwareTrait;

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            ProgramFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->loadData() as $data) {
            /** @var Program $program */
            foreach ($data['programs'] as $program) {
                /** @var Company $company */
                foreach ($data['companies'] as $company) {
                    $participation = new Participation($program, $company, (string) $this->faker->randomFloat(2, 0, 1));
                    $manager->persist($participation);
                }

                $manager->flush();
            }
        }
    }

    private function loadData(): iterable
    {
        $companies = CompanyFixtures::CA_SHORTCODE;
        \array_walk(
            $companies,
            fn (&$shortCode) => $shortCode = $this->getReference(CompanyFixtures::REFERENCE_PREFIX . $shortCode)
        );

        yield 'programs with all regional banks' => [
            'programs' => $this->getReferences([
                ProgramFixtures::PROGRAM_AGRICULTURE_DRAFT,
                ProgramFixtures::PROGRAM_AGRICULTURE_COMMERCIALIZED,
                ProgramFixtures::PROGRAM_CORPORATE_PAUSED,
                ProgramFixtures::PROGRAM_CORPORATE_ARCHIVED,
            ]),
            'companies' => $companies,
        ];
        yield 'programs with some regional banks' => [
            'programs' => $this->getReferences([
                ProgramFixtures::PROGRAM_AGRICULTURE_PAUSED,
                ProgramFixtures::PROGRAM_AGRICULTURE_ARCHIVED,
                ProgramFixtures::PROGRAM_CORPORATE_DRAFT,
                ProgramFixtures::PROGRAM_CORPORATE_COMMERCIALIZED,
            ]),
            'companies' => $this->getReferences([
                CompanyFixtures::REFERENCE_PREFIX . CompanyFixtures::CA_SHORTCODE['CA des Savoie'],
                CompanyFixtures::REFERENCE_PREFIX . CompanyFixtures::CA_SHORTCODE['CA Toulouse 31'],
            ]),
        ];
    }
}
