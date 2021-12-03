<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\Entity\Staff;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplateField;
use KLS\Test\Core\DataFixtures\AbstractFixtures;

class ReportingTemplateFixtures extends AbstractFixtures implements DependentFixtureInterface
{
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
        foreach ($this->loadData() as $reference => $reportingTemplateData) {
            $reportingTemplate = new ReportingTemplate(
                $reportingTemplateData['program'],
                $reportingTemplateData['name'],
                $reportingTemplateData['addedBy']
            );

            $this->setPublicId($reportingTemplate, $reference);
            $this->addReference($reference, $reportingTemplate);
            $manager->persist($reportingTemplate);

            foreach ($reportingTemplateData['fields'] as $field) {
                $reportingTemplateField = new ReportingTemplateField($reportingTemplate, $field);
                $manager->persist($reportingTemplateField);
            }

            $manager->flush();
        }
    }

    private function loadData(): iterable
    {
        /** @var Program $program */
        $program = $this->getReference(ProgramFixtures::REFERENCE_PAUSED);
        /** @var Staff $addedBy */
        $addedBy = $program->getManagingCompany()->getStaff()->current();

        yield 'reporting-template-1' => [
            'program' => $program,
            'name'    => 'Template 1',
            'addedBy' => $addedBy,
            'fields'  => [
                $this->getReference('field-' . FieldAlias::CREATION_IN_PROGRESS),
                $this->getReference('field-' . FieldAlias::BENEFICIARY_NAME),
                $this->getReference('field-' . FieldAlias::COMPANY_NAME),
                $this->getReference('field-' . FieldAlias::ACTIVITY_DEPARTMENT),
                $this->getReference('field-' . FieldAlias::RECEIVING_GRANT),
                $this->getReference('field-' . FieldAlias::AID_INTENSITY),
                $this->getReference('field-' . FieldAlias::PROJECT_GRANT),
                $this->getReference('field-' . FieldAlias::SUPPORTING_GENERATIONS_RENEWAL),
                $this->getReference('field-' . FieldAlias::FINANCING_OBJECT_NAME),
                $this->getReference('field-' . FieldAlias::LOAN_DURATION),
                $this->getReference('field-' . FieldAlias::INVESTMENT_LOCATION),
                $this->getReference('field-' . FieldAlias::RESERVATION_NAME),
                $this->getReference('field-' . FieldAlias::BORROWER_TYPE_GRADE),
                $this->getReference('field-' . FieldAlias::LOAN_NEW_MATURITY),
            ],
        ];
        yield 'reporting-template-2' => [
            'program' => $program,
            'name'    => 'Template 2',
            'addedBy' => $addedBy,
            'fields'  => [],
        ];
    }
}
