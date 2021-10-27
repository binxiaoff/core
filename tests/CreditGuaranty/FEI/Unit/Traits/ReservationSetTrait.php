<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Traits;

use DateTimeImmutable;
use Exception;
use KLS\Core\Entity\CompanyGroup;
use KLS\Core\Entity\CompanyGroupTag;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\CreditGuaranty\FEI\Entity\Borrower;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\Project;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;

trait ReservationSetTrait
{
    use UserStaffTrait;

    /**
     * @throws Exception
     */
    protected function createReservation(): Reservation
    {
        $program = new Program(
            'Program',
            new CompanyGroupTag(new CompanyGroup('Company Group'), 'code'),
            new Money('EUR', '42'),
            $this->createStaff()
        );

        return new Reservation($program, $this->createStaff());
    }

    protected function withBorrower(Reservation $reservation): void
    {
        $program = $reservation->getProgram();

        $borrowerTypeField = new Field(
            'borrower_type',
            Field::TAG_ELIGIBILITY,
            'test',
            'list',
            'borrower',
            'borrowerType',
            'ProgramChoiceOption',
            Borrower::class,
            false,
            null,
            null
        );
        $legalFormField = new Field(
            'legal_form',
            Field::TAG_ELIGIBILITY,
            'test',
            'list',
            'borrower',
            'legalForm',
            'ProgramChoiceOption',
            Borrower::class,
            false,
            null,
            null
        );
        $activityCountryField = new Field(
            'activity_country',
            Field::TAG_ELIGIBILITY,
            'test',
            'list',
            'borrower',
            'addressCountry',
            'ProgramChoiceOption',
            Borrower::class,
            false,
            null,
            ['FR']
        );
        $activityDepartmentField = new Field(
            'activity_department',
            Field::TAG_ELIGIBILITY,
            'test',
            'list',
            'borrower',
            'addressDepartment',
            'ProgramChoiceOption',
            Borrower::class,
            false,
            null,
            null
        );

        $reservation->getBorrower()
            ->setBeneficiaryName('Borrower Name')
            ->setBorrowerType(new ProgramChoiceOption($program, 'borrower type', $borrowerTypeField))
            ->setYoungFarmer(true)
            ->setCreationInProgress(false)
            ->setSubsidiary(true)
            ->setCompanyName('Borrower Company')
            ->setActivityStartDate(new DateTimeImmutable())
            ->setAddressStreet('42 rue de de la paix')
            ->setAddressCity('Paris')
            ->setAddressPostCode('75042')
            ->setAddressDepartment(new ProgramChoiceOption($program, 'department', $activityDepartmentField))
            ->setAddressCountry(new ProgramChoiceOption($program, 'FR', $activityCountryField))
            ->setSiret(\str_repeat('1', 14))
            ->setLegalForm(new ProgramChoiceOption($program, 'legal form', $legalFormField))
            ->setEmployeesNumber(42)
            ->setTurnover(new NullableMoney('EUR', '128'))
            ->setTotalAssets(new NullableMoney('EUR', '2048'))
            ->setGrade('D')
        ;
    }

    protected function withProject(Reservation $reservation): void
    {
        $program = $reservation->getProgram();

        $investmentThematicField = new Field(
            'investment_thematic',
            Field::TAG_ELIGIBILITY,
            'test',
            'list',
            'project',
            'investmentThematics',
            'Collection',
            Project::class,
            false,
            null,
            null
        );
        $investmentTypeField = new Field(
            'investment_type',
            Field::TAG_ELIGIBILITY,
            'test',
            'list',
            'project',
            'investmentType',
            'ProgramChoiceOption',
            Project::class,
            false,
            null,
            null
        );
        $aidIntensityField = new Field(
            'aid_intensity',
            Field::TAG_ELIGIBILITY,
            'test',
            'list',
            'project',
            'aidIntensity',
            'ProgramChoiceOption',
            Project::class,
            false,
            null,
            null
        );
        $additionalGuaranty = new Field(
            'additional_guaranty',
            Field::TAG_ELIGIBILITY,
            'test',
            'list',
            'project',
            'additionalGuaranty',
            'ProgramChoiceOption',
            Project::class,
            false,
            null,
            null
        );
        $agriculturalBranch = new Field(
            'agricultural_branch',
            Field::TAG_ELIGIBILITY,
            'test',
            'list',
            'project',
            'agriculturalBranch',
            'ProgramChoiceOption',
            Project::class,
            false,
            null,
            null
        );

        $reservation->getProject()
            ->addInvestmentThematic(new ProgramChoiceOption($program, 'Thématique A', $investmentThematicField))
            ->addInvestmentThematic(new ProgramChoiceOption($program, 'Thématique B', $investmentThematicField))
            ->addInvestmentThematic(new ProgramChoiceOption($program, 'Thématique C', $investmentThematicField))
            ->setInvestmentType(new ProgramChoiceOption($program, 'investment type', $investmentTypeField))
            ->setAidIntensity(new ProgramChoiceOption($program, '0.42', $aidIntensityField))
            ->setAdditionalGuaranty(new ProgramChoiceOption($program, 'additional guaranty', $additionalGuaranty))
            ->setAgriculturalBranch(new ProgramChoiceOption($program, 'agricultural branch', $agriculturalBranch))
            ->setFundingMoney(new NullableMoney('EUR', '42'))
        ;
    }

    protected function createFinancingObject(
        Reservation $reservation,
        bool $supportingGenerationsRenewal
    ): FinancingObject {
        $program = $reservation->getProgram();

        $financingObjectTypeField = new Field(
            'financing_object_type',
            Field::TAG_ELIGIBILITY,
            'test',
            'list',
            'financingObjects',
            'financingObjectType',
            'ProgramChoiceOption',
            FinancingObject::class,
            false,
            null,
            null
        );
        $loanTypeField = new Field(
            'loan_type',
            Field::TAG_ELIGIBILITY,
            'test',
            'list',
            'financingObjects',
            'loanType',
            'ProgramChoiceOption',
            FinancingObject::class,
            false,
            null,
            ['loan type 1', 'loan type 2']
        );

        return (
            new FinancingObject(
                $reservation,
                new Money('EUR', '42'),
                false,
                'financing object name'
            ))
                ->setSupportingGenerationsRenewal($supportingGenerationsRenewal)
                ->setFinancingObjectType(
                    new ProgramChoiceOption($program, 'financing object test', $financingObjectTypeField)
                )
                ->setLoanType(new ProgramChoiceOption($program, 'loan type 2', $loanTypeField))
                ->setLoanDuration(4)
                ->setLoanDeferral(1)
        ;
    }
}
