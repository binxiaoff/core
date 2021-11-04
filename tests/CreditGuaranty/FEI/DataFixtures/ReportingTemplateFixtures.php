<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\Entity\Staff;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\Test\Core\DataFixtures\AbstractFixtures;

class ReportingTemplateFixtures extends AbstractFixtures implements DependentFixtureInterface
{
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
        /** @var Program $program */
        $program = $this->getReference(ProgramFixtures::REFERENCE_PAUSED);
        /** @var Staff $addedBy */
        $addedBy = $program->getManagingCompany()->getStaff()->current();

        foreach (\range(1, 3) as $index) {
            $reference = 'reporting-template-' . $index;

            $reportingTemplate = new ReportingTemplate($program, 'Template ' . $index, $addedBy);
            $this->setPublicId($reportingTemplate, $reference);
            $this->addReference($reference, $reportingTemplate);
            $manager->persist($reportingTemplate);
        }

        $manager->flush();
    }
}
