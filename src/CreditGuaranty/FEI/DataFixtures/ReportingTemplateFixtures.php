<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\DataFixtures\StaffFixtures;
use KLS\Core\Entity\Staff;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplateField;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ReportingTemplateFixtures extends AbstractFixtures implements DependentFixtureInterface
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
            FieldFixtures::class,
            ProgramFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var Program $program */
        $program = $this->getReference(ProgramFixtures::REFERENCE_PAUSED);
        /** @var Staff $staff */
        $staff = $this->getReference(StaffFixtures::CASA);

        foreach (\range(1, 3) as $i) {
            $reportingTemplate = new ReportingTemplate($program, \sprintf('Reporting template P%s #%s', $program->getId(), $i), $staff);
            $manager->persist($reportingTemplate);
            $this->addReference(\sprintf('reporting_template_p%s_%s', $program->getId(), $i), $reportingTemplate);
        }

        $manager->flush();

        $fields = $this->fieldRepository->findAll();

        foreach (\range(1, 2) as $i) {
            /** @var ReportingTemplate $reportingTemplate */
            $reportingTemplate = $this->getReference(\sprintf('reporting_template_p%s_%s', $program->getId(), $i));

            \shuffle($fields);
            foreach ($fields as $field) {
                $reportingTemplateField = new ReportingTemplateField($reportingTemplate, $field);
                $manager->persist($reportingTemplateField);
            }
        }

        $manager->flush();
    }
}
