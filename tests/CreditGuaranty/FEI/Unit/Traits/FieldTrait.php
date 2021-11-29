<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Traits;

use KLS\CreditGuaranty\FEI\Entity\Borrower;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\Project;

trait FieldTrait
{
    protected function createActivityCountryField(): Field
    {
        return new Field(
            FieldAlias::ACTIVITY_COUNTRY,
            Field::TAG_ELIGIBILITY,
            'profile',
            'list',
            'borrower',
            'addressCountry',
            'ProgramChoiceOption',
            Borrower::class,
            false,
            null,
            null
        );
    }

    protected function createActivityDepartmentField(): Field
    {
        return new Field(
            FieldAlias::ACTIVITY_DEPARTMENT,
            Field::TAG_ELIGIBILITY,
            'profile',
            'list',
            'borrower',
            'addressDepartment',
            'ProgramChoiceOption',
            Borrower::class,
            false,
            null,
            null
        );
    }

    protected function createActivityPostCodeField(): Field
    {
        return new Field(
            FieldAlias::ACTIVITY_POST_CODE,
            Field::TAG_ELIGIBILITY,
            'profile',
            'other',
            'borrower',
            'addressPostCode',
            'string',
            Borrower::class,
            false,
            null,
            null
        );
    }

    protected function createActivityStartDateField(): Field
    {
        return new Field(
            FieldAlias::ACTIVITY_START_DATE,
            Field::TAG_ELIGIBILITY,
            'profile',
            'other',
            'borrower',
            'activityStartDate',
            'DateTimeImmutable',
            Borrower::class,
            false,
            null,
            null
        );
    }

    protected function createAdditionalGuarantyField(): Field
    {
        return new Field(
            FieldAlias::ADDITIONAL_GUARANTY,
            Field::TAG_ELIGIBILITY,
            'project',
            'list',
            'project',
            'additionalGuaranty',
            'ProgramChoiceOption',
            Project::class,
            true,
            null,
            null
        );
    }

    protected function createAidIntensityField(): Field
    {
        return new Field(
            FieldAlias::AID_INTENSITY,
            Field::TAG_ELIGIBILITY,
            'project',
            'list',
            'project',
            'aidIntensity',
            'ProgramChoiceOption',
            Project::class,
            true,
            null,
            null
        );
    }

    protected function createAgriculturalBranchField(): Field
    {
        return new Field(
            FieldAlias::AGRICULTURAL_BRANCH,
            Field::TAG_ELIGIBILITY,
            'project',
            'list',
            'project',
            'agriculturalBranch',
            'ProgramChoiceOption',
            Project::class,
            true,
            null,
            null
        );
    }

    protected function createBeneficiaryNameField(): Field
    {
        return new Field(
            FieldAlias::BENEFICIARY_NAME,
            Field::TAG_ELIGIBILITY,
            'profile',
            'other',
            'borrower',
            'beneficiaryName',
            'string',
            Borrower::class,
            false,
            null,
            null
        );
    }

    protected function createBorrowerTypeField(): Field
    {
        return new Field(
            FieldAlias::BORROWER_TYPE,
            Field::TAG_ELIGIBILITY,
            'profile',
            'list',
            'borrower',
            'borrowerType',
            'ProgramChoiceOption',
            Borrower::class,
            false,
            null,
            null
        );
    }

    protected function createCompanyNameField(): Field
    {
        return new Field(
            FieldAlias::COMPANY_NAME,
            Field::TAG_ELIGIBILITY,
            'profile',
            'other',
            'borrower',
            'companyName',
            'string',
            Borrower::class,
            false,
            null,
            null
        );
    }

    protected function createCreationInProgressField(): Field
    {
        return new Field(
            'creation_in_progress',
            Field::TAG_ELIGIBILITY,
            'profile',
            'bool',
            'borrower',
            'creationInProgress',
            'bool',
            Borrower::class,
            false,
            null,
            null
        );
    }

    protected function createEmployeesNumberField(): Field
    {
        return new Field(
            FieldAlias::EMPLOYEES_NUMBER,
            Field::TAG_ELIGIBILITY,
            'profile',
            'other',
            'borrower',
            'employeesNumber',
            'int',
            Borrower::class,
            true,
            'person',
            null
        );
    }

    protected function createFinancingObjectTypeField(): Field
    {
        return new Field(
            FieldAlias::FINANCING_OBJECT_TYPE,
            Field::TAG_ELIGIBILITY,
            'loan',
            'list',
            'financingObjects',
            'financingObjectType',
            'ProgramChoiceOption',
            FinancingObject::class,
            true,
            null,
            null
        );
    }

    protected function createFirstReleaseDateField(): Field
    {
        return new Field(
            FieldAlias::FIRST_RELEASE_DATE,
            Field::TAG_INFO,
            'loan',
            'other',
            'financingObjects',
            'firstReleaseDate',
            'DateTimeImmutable',
            FinancingObject::class,
            false,
            null,
            null
        );
    }

    protected function createInvestmentThematicField(): Field
    {
        return new Field(
            FieldAlias::INVESTMENT_THEMATIC,
            Field::TAG_ELIGIBILITY,
            'project',
            'list',
            'project',
            'investmentThematics',
            'Collection',
            Project::class,
            true,
            null,
            null
        );
    }

    protected function createInvestmentTypeField(): Field
    {
        return new Field(
            FieldAlias::INVESTMENT_TYPE,
            Field::TAG_ELIGIBILITY,
            'project',
            'list',
            'project',
            'investmentType',
            'ProgramChoiceOption',
            Project::class,
            true,
            null,
            null
        );
    }

    protected function createLegalFormField(): Field
    {
        return new Field(
            FieldAlias::LEGAL_FORM,
            Field::TAG_ELIGIBILITY,
            'profile',
            'list',
            'borrower',
            'legalForm',
            'ProgramChoiceOption',
            Borrower::class,
            true,
            null,
            null
        );
    }

    protected function createLoanDeferralField(): Field
    {
        return new Field(
            FieldAlias::LOAN_DEFERRAL,
            Field::TAG_ELIGIBILITY,
            'loan',
            'other',
            'financingObjects',
            'loanDeferral',
            'int',
            FinancingObject::class,
            false,
            null,
            null
        );
    }

    protected function createLoanDurationField(): Field
    {
        return new Field(
            FieldAlias::LOAN_DURATION,
            Field::TAG_ELIGIBILITY,
            'loan',
            'other',
            'financingObjects',
            'loanDuration',
            'int',
            FinancingObject::class,
            false,
            null,
            null
        );
    }

    protected function createLoanMoneyField(): Field
    {
        return new Field(
            FieldAlias::LOAN_MONEY,
            Field::TAG_ELIGIBILITY,
            'loan',
            'other',
            'financingObjects',
            'loanMoney',
            'MoneyInterface',
            FinancingObject::class,
            true,
            'money',
            null
        );
    }

    protected function createLoanNafCodeField(): Field
    {
        return new Field(
            FieldAlias::LOAN_NAF_CODE,
            Field::TAG_ELIGIBILITY,
            'loan',
            'list',
            'financingObjects',
            'loanNafCode',
            'ProgramChoiceOption',
            FinancingObject::class,
            true,
            null,
            null
        );
    }

    protected function createLoanRemainingCapitalField(): Field
    {
        return new Field(
            FieldAlias::LOAN_REMAINING_CAPITAL,
            Field::TAG_IMPORTED,
            'loan',
            'other',
            'financingObjects',
            'remainingCapital',
            'NullableMoney',
            FinancingObject::class,
            false,
            null,
            null
        );
    }

    protected function createLoanTypeField(): Field
    {
        return new Field(
            FieldAlias::LOAN_TYPE,
            Field::TAG_ELIGIBILITY,
            'loan',
            'list',
            'financingObjects',
            'loanType',
            'ProgramChoiceOption',
            FinancingObject::class,
            true,
            null,
            null
        );
    }

    protected function createProgramDurationField(): Field
    {
        return new Field(
            FieldAlias::PROGRAM_DURATION,
            Field::TAG_INFO,
            'program',
            'other',
            'program',
            'guarantyDuration',
            'int',
            Program::class,
            false,
            null,
            null
        );
    }

    protected function createProjectTotalAmountField(): Field
    {
        return new Field(
            FieldAlias::PROJECT_TOTAL_AMOUNT,
            Field::TAG_ELIGIBILITY,
            'project',
            'other',
            'project',
            'fundingMoney',
            'MoneyInterface',
            Project::class,
            true,
            'money',
            null
        );
    }

    protected function createReceivingGrantField(): Field
    {
        return new Field(
            FieldAlias::RECEIVING_GRANT,
            Field::TAG_ELIGIBILITY,
            'project',
            'bool',
            'project',
            'receivingGrant',
            'bool',
            Project::class,
            true,
            null,
            null
        );
    }

    protected function createReservationSigningDateField(): Field
    {
        return new Field(
            FieldAlias::RESERVATION_SIGNING_DATE,
            Field::TAG_INFO,
            'reservation',
            'other',
            'signingDate',
            '',
            'DateTimeImmutable',
            '',
            false,
            null,
            null
        );
    }

    protected function createReservationStatusField(): Field
    {
        return new Field(
            FieldAlias::RESERVATION_STATUS,
            Field::TAG_INFO,
            'reservation',
            'other',
            'currentStatus',
            '',
            'int',
            '',
            false,
            null,
            null
        );
    }

    protected function createRegistrationNumberField(): Field
    {
        return new Field(
            FieldAlias::REGISTRATION_NUMBER,
            Field::TAG_ELIGIBILITY,
            'profile',
            'other',
            'borrower',
            'registrationNumber',
            'string',
            Borrower::class,
            false,
            null,
            null
        );
    }

    protected function createSupportingGenerationsRenewalField(): Field
    {
        return new Field(
            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL,
            Field::TAG_ELIGIBILITY,
            'loan',
            'bool',
            'financingObjects',
            'supportingGenerationsRenewal',
            'bool',
            FinancingObject::class,
            true,
            null,
            null
        );
    }

    protected function createTotalAssetsField(): Field
    {
        return new Field(
            FieldAlias::TOTAL_ASSETS,
            Field::TAG_ELIGIBILITY,
            'profile',
            'other',
            'borrower',
            'totalAssets',
            'MoneyInterface',
            Borrower::class,
            true,
            'money',
            null
        );
    }

    protected function createTotalEsbField(): Field
    {
        return new Field(
            FieldAlias::TOTAL_GROSS_SUBSIDY_EQUIVALENT,
            Field::TAG_CALCUL,
            'project',
            'other',
            'project',
            'totalGrossSubsidyEquivalent',
            'MoneyInterface',
            Project::class,
            false,
            null,
            null
        );
    }

    protected function createTurnoverField(): Field
    {
        return new Field(
            FieldAlias::TURNOVER,
            Field::TAG_ELIGIBILITY,
            'profile',
            'other',
            'borrower',
            'turnover',
            'MoneyInterface',
            Borrower::class,
            true,
            'money',
            null
        );
    }

    protected function createYoungFarmerField(): Field
    {
        return new Field(
            FieldAlias::YOUNG_FARMER,
            Field::TAG_ELIGIBILITY,
            'borrower',
            'bool',
            'borrower',
            'youngFarmer',
            'MoneyInterface',
            Borrower::class,
            true,
            null,
            null
        );
    }
}
