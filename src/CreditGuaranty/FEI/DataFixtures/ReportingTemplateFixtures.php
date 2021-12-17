<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\DataFixtures\StaffFixtures;
use KLS\Core\Entity\Staff;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
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
        /** @var Staff $staff */
        $staff = $this->getReference(StaffFixtures::CASA);

        foreach ($this->loadData() as $reference => $data) {
            $reportingTemplate = new ReportingTemplate($data['program'], $data['name'], $staff);
            $manager->persist($reportingTemplate);
            $this->addReference($reference, $reportingTemplate);
        }

        $manager->flush();

        foreach ($this->loadData() as $reference => $data) {
            /** @var ReportingTemplate $reportingTemplate */
            $reportingTemplate = $this->getReference($reference);

            \shuffle($data['fields']);

            foreach ($data['fields'] as $field) {
                $reportingTemplateField = new ReportingTemplateField($reportingTemplate, $field);
                $manager->persist($reportingTemplateField);
            }
        }

        $manager->flush();
    }

    private function loadData(): iterable
    {
        $programs = $this->getReferences([
            ProgramFixtures::PROGRAM_AGRICULTURE_PAUSED,
            ProgramFixtures::PROGRAM_CORPORATE_PAUSED,
        ]);

        $allFields  = $this->fieldRepository->findAll();
        $someFields = $this->fieldRepository->findBy([
            'fieldAlias' => [
                FieldAlias::PROGRAM_DURATION,
                FieldAlias::RESERVATION_CREATION_DATE,
                FieldAlias::RESERVATION_STATUS,
                FieldAlias::ACTIVITY_START_DATE,
                FieldAlias::BENEFICIARY_NAME,
                FieldAlias::BORROWER_TYPE,
                FieldAlias::BORROWER_TYPE_GRADE,
                FieldAlias::COMPANY_NAF_CODE,
                FieldAlias::CREATION_IN_PROGRESS,
                FieldAlias::TOTAL_ASSETS,
                FieldAlias::AGRICULTURAL_BRANCH,
                FieldAlias::ELIGIBLE_FEI_CREDIT,
                FieldAlias::INVESTMENT_DEPARTMENT,
                FieldAlias::INVESTMENT_THEMATIC,
                FieldAlias::INVESTMENT_TYPE,
                FieldAlias::PROJECT_DETAIL,
                FieldAlias::PROJECT_GRANT,
                FieldAlias::TOTAL_GROSS_SUBSIDY_EQUIVALENT,
                FieldAlias::FINANCING_OBJECT_TYPE,
                FieldAlias::FIRST_RELEASE_DATE,
                FieldAlias::LOAN_MONEY,
                FieldAlias::LOAN_NEW_MATURITY,
                FieldAlias::LOAN_NUMBER,
                FieldAlias::LOAN_OPERATION_NUMBER,
                FieldAlias::LOAN_REMAINING_CAPITAL,
            ],
        ]);
        $someProfileFields = $this->fieldRepository->findBy([
            'fieldAlias' => [
                FieldAlias::ACTIVITY_START_DATE,
                FieldAlias::BORROWER_TYPE_GRADE,
                FieldAlias::COMPANY_NAF_CODE,
                FieldAlias::ECONOMICALLY_VIABLE,
                FieldAlias::LOAN_ALLOWED_REFINANCE_RESTRUCTURE,
            ],
        ]);
        $someProjectFields = $this->fieldRepository->findBy([
            'fieldAlias' => [
                FieldAlias::CREDIT_EXCLUDING_FEI,
                FieldAlias::INVESTMENT_DEPARTMENT,
                FieldAlias::PROJECT_DETAIL,
                FieldAlias::PROJECT_TOTAL_AMOUNT,
                FieldAlias::TOTAL_GROSS_SUBSIDY_EQUIVALENT,
            ],
        ]);
        $someLoanFields = $this->fieldRepository->findBy([
            'fieldAlias' => [
                FieldAlias::FINANCING_OBJECT_NAME,
                FieldAlias::FINANCING_OBJECT_TYPE,
                FieldAlias::INVESTMENT_LOCATION,
                FieldAlias::LOAN_NEW_MATURITY,
                FieldAlias::LOAN_REMAINING_CAPITAL,
            ],
        ]);

        foreach ($programs as $program) {
            yield \sprintf('reporting-template:p%s:all', $program->getId()) => [
                'program' => $program,
                'name'    => \sprintf('Table PG%sA - Tout', $program->getId()),
                'fields'  => $allFields,
            ];
            yield \sprintf('reporting-template:p%s:some', $program->getId()) => [
                'program' => $program,
                'name'    => \sprintf('Table PG%sB - Patout', $program->getId()),
                'fields'  => $someFields,
            ];
            yield \sprintf('reporting-template:p%s:profile', $program->getId()) => [
                'program' => $program,
                'name'    => \sprintf('Table PG%sC - Bénéficiaire', $program->getId()),
                'fields'  => $someProfileFields,
            ];
            yield \sprintf('reporting-template:p%s:project', $program->getId()) => [
                'program' => $program,
                'name'    => \sprintf('Table PG%sD - Projet', $program->getId()),
                'fields'  => $someProjectFields,
            ];
            yield \sprintf('reporting-template:p%s:loan', $program->getId()) => [
                'program' => $program,
                'name'    => \sprintf('Table PG%sE - Prêt', $program->getId()),
                'fields'  => $someLoanFields,
            ];
        }
    }
}
