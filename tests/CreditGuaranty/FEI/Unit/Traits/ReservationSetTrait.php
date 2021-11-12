<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Traits;

use DateTimeImmutable;
use Exception;
use KLS\Core\Entity\CompanyGroup;
use KLS\Core\Entity\CompanyGroupTag;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;

trait ReservationSetTrait
{
    use UserStaffTrait;
    use FieldTrait;

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

        $borrowerTypeField       = $this->createBorrowerTypeField();
        $legalFormField          = $this->createLegalFormField();
        $activityCountryField    = $this->createActivityCountryField();
        $activityDepartmentField = $this->createActivityDepartmentField();

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

        $investmentThematicField = $this->createInvestmentThematicField();
        $investmentTypeField     = $this->createInvestmentTypeField();
        $aidIntensityField       = $this->createAidIntensityField();
        $additionalGuaranty      = $this->createAdditionalGuarantyField();
        $agriculturalBranch      = $this->createAgriculturalBranchField();

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

        $financingObjectTypeField = $this->createFinancingObjectTypeField();
        $loanTypeField            = $this->createLoanTypeField();

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
