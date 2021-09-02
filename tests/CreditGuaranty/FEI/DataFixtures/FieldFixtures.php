<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use KLS\CreditGuaranty\FEI\Entity\Borrower;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Project;
use KLS\Test\Core\DataFixtures\AbstractFixtures;

class FieldFixtures extends AbstractFixtures
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->loadData() as $reference => $fieldData) {
            $field = new Field(
                $fieldData['fieldAlias'],
                $fieldData['category'],
                $fieldData['type'],
                $fieldData['reservationPropertyName'],
                $fieldData['propertyPath'],
                $fieldData['propertyType'],
                $fieldData['objectClass'],
                $fieldData['comparable'],
                $fieldData['unit'],
                $fieldData['predefinedItems']
            );

            $this->setPublicId($field, $reference);
            $this->addReference($reference, $field);

            $manager->persist($field);
        }

        $manager->flush();
    }

    private function loadData(): iterable
    {
        yield 'field-beneficiary_name' => [
            'fieldAlias'              => 'beneficiary_name',
            'category'                => 'profile',
            'type'                    => 'other',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'beneficiaryName',
            'propertyType'            => 'string',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-borrower_type' => [
            'fieldAlias'              => 'borrower_type',
            'category'                => 'profile',
            'type'                    => 'list',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'borrowerType',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-young_farmer' => [
            'fieldAlias'              => 'young_farmer',
            'category'                => 'profile',
            'type'                    => 'bool',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'youngFarmer',
            'propertyType'            => 'bool',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-creation_in_progress' => [
            'fieldAlias'              => 'creation_in_progress',
            'category'                => 'profile',
            'type'                    => 'bool',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'creationInProgress',
            'propertyType'            => 'bool',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-subsidiary' => [
            'fieldAlias'              => 'subsidiary',
            'category'                => 'profile',
            'type'                    => 'bool',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'subsidiary',
            'propertyType'            => 'bool',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-company_name' => [
            'fieldAlias'              => 'company_name',
            'category'                => 'profile',
            'type'                    => 'other',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'companyName',
            'propertyType'            => 'string',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-activity_street' => [
            'fieldAlias'              => 'activity_street',
            'category'                => 'profile',
            'type'                    => 'other',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'addressStreet',
            'propertyType'            => 'string',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-activity_post_code' => [
            'fieldAlias'              => 'activity_post_code',
            'category'                => 'profile',
            'type'                    => 'other',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'addressPostCode',
            'propertyType'            => 'string',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-activity_city' => [
            'fieldAlias'              => 'activity_city',
            'category'                => 'profile',
            'type'                    => 'other',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'addressCity',
            'propertyType'            => 'string',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-activity_department' => [
            'fieldAlias'              => 'activity_department',
            'category'                => 'profile',
            'type'                    => 'other',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'addressDepartment',
            'propertyType'            => 'string',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-activity_country' => [
            'fieldAlias'              => 'activity_country',
            'category'                => 'profile',
            'type'                    => 'list',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'addressCountry',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-activity_start_date' => [
            'fieldAlias'              => 'activity_start_date',
            'category'                => 'profile',
            'type'                    => 'other',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'activityStartDate',
            'propertyType'            => 'DateTimeImmutable',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-siret' => [
            'fieldAlias'              => 'siret',
            'category'                => 'profile',
            'type'                    => 'other',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'siret',
            'propertyType'            => 'string',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-tax_number' => [
            'fieldAlias'              => 'tax_number',
            'category'                => 'profile',
            'type'                    => 'other',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'taxNumber',
            'propertyType'            => 'string',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-legal_form' => [
            'fieldAlias'              => 'legal_form',
            'category'                => 'profile',
            'type'                    => 'list',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'legalForm',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => ['SARL', 'SAS', 'SASU', 'EURL', 'SA', 'SELAS'],
        ];
        yield 'field-company_naf_code' => [
            'fieldAlias'              => 'company_naf_code',
            'category'                => 'profile',
            'type'                    => 'other',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'companyNafCode',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-employees_number' => [
            'fieldAlias'              => 'employees_number',
            'category'                => 'profile',
            'type'                    => 'other',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'employeesNumber',
            'propertyType'            => 'int',
            'objectClass'             => Borrower::class,
            'comparable'              => true,
            'unit'                    => 'person',
            'predefinedItems'         => null,
        ];
        yield 'field-exploitation_size' => [
            'fieldAlias'              => 'exploitation_size',
            'category'                => 'profile',
            'type'                    => 'other',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'exploitationSize',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => Borrower::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-turnover' => [
            'fieldAlias'              => 'turnover',
            'category'                => 'profile',
            'type'                    => 'other',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'turnover',
            'propertyType'            => 'MoneyInterface',
            'objectClass'             => Borrower::class,
            'comparable'              => true,
            'unit'                    => 'money',
            'predefinedItems'         => null,
        ];
        yield 'field-total_assets' => [
            'fieldAlias'              => 'total_assets',
            'category'                => 'profile',
            'type'                    => 'other',
            'reservationPropertyName' => 'borrower',
            'propertyPath'            => 'totalAssets',
            'propertyType'            => 'MoneyInterface',
            'objectClass'             => Borrower::class,
            'comparable'              => true,
            'unit'                    => 'money',
            'predefinedItems'         => null,
        ];
        yield 'field-receiving_grant' => [
            'fieldAlias'              => 'receiving_grant',
            'category'                => 'project',
            'type'                    => 'bool',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'receivingGrant',
            'propertyType'            => 'bool',
            'objectClass'             => Project::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-investment_street' => [
            'fieldAlias'              => 'investment_street',
            'category'                => 'project',
            'type'                    => 'other',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'addressStreet',
            'propertyType'            => 'string',
            'objectClass'             => Project::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-investment_post_code' => [
            'fieldAlias'              => 'investment_post_code',
            'category'                => 'project',
            'type'                    => 'other',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'addressPostCode',
            'propertyType'            => 'string',
            'objectClass'             => Project::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-investment_city' => [
            'fieldAlias'              => 'investment_city',
            'category'                => 'project',
            'type'                    => 'other',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'addressCity',
            'propertyType'            => 'string',
            'objectClass'             => Project::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-investment_department' => [
            'fieldAlias'              => 'investment_department',
            'category'                => 'project',
            'type'                    => 'other',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'addressDepartment',
            'propertyType'            => 'string',
            'objectClass'             => Project::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-investment_country' => [
            'fieldAlias'              => 'investment_country',
            'category'                => 'project',
            'type'                    => 'list',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'addressCountry',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => Project::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-investment_thematic' => [
            'fieldAlias'              => 'investment_thematic',
            'category'                => 'project',
            'type'                    => 'list',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'investmentThematic',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => Project::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-investment_type' => [
            'fieldAlias'              => 'investment_type',
            'category'                => 'project',
            'type'                    => 'list',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'investmentType',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => Project::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-aid_intensity' => [
            'fieldAlias'              => 'aid_intensity',
            'category'                => 'project',
            'type'                    => 'list',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'aidIntensity',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => Project::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-additional_guaranty' => [
            'fieldAlias'              => 'additional_guaranty',
            'category'                => 'project',
            'type'                    => 'list',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'additionalGuaranty',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => Project::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-agricultural_branch' => [
            'fieldAlias'              => 'agricultural_branch',
            'category'                => 'project',
            'type'                    => 'list',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'agriculturalBranch',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => Project::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-project_total_amount' => [
            'fieldAlias'              => 'project_total_amount',
            'category'                => 'project',
            'type'                    => 'other',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'fundingMoney',
            'propertyType'            => 'MoneyInterface',
            'objectClass'             => Project::class,
            'comparable'              => true,
            'unit'                    => 'money',
            'predefinedItems'         => null,
        ];
        yield 'field-project_contribution' => [
            'fieldAlias'              => 'project_contribution',
            'category'                => 'project',
            'type'                    => 'other',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'contribution',
            'propertyType'            => 'MoneyInterface',
            'objectClass'             => Project::class,
            'comparable'              => true,
            'unit'                    => 'money',
            'predefinedItems'         => null,
        ];
        yield 'field-eligible_fei_credit' => [
            'fieldAlias'              => 'eligible_fei_credit',
            'category'                => 'project',
            'type'                    => 'other',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'eligibleFeiCredit',
            'propertyType'            => 'MoneyInterface',
            'objectClass'             => Project::class,
            'comparable'              => true,
            'unit'                    => 'money',
            'predefinedItems'         => null,
        ];
        yield 'field-total_fei_credit' => [
            'fieldAlias'              => 'total_fei_credit',
            'category'                => 'project',
            'type'                    => 'other',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'totalFeiCredit',
            'propertyType'            => 'MoneyInterface',
            'objectClass'             => Project::class,
            'comparable'              => true,
            'unit'                    => 'money',
            'predefinedItems'         => null,
        ];
        yield 'field-tangible_fei_credit' => [
            'fieldAlias'              => 'tangible_fei_credit',
            'category'                => 'project',
            'type'                    => 'other',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'tangibleFeiCredit',
            'propertyType'            => 'MoneyInterface',
            'objectClass'             => Project::class,
            'comparable'              => true,
            'unit'                    => 'money',
            'predefinedItems'         => null,
        ];
        yield 'field-intangible_fei_credit' => [
            'fieldAlias'              => 'intangible_fei_credit',
            'category'                => 'project',
            'type'                    => 'other',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'intangibleFeiCredit',
            'propertyType'            => 'MoneyInterface',
            'objectClass'             => Project::class,
            'comparable'              => true,
            'unit'                    => 'money',
            'predefinedItems'         => null,
        ];
        yield 'field-credit_excluding_fei' => [
            'fieldAlias'              => 'credit_excluding_fei',
            'category'                => 'project',
            'type'                    => 'other',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'creditExcludingFei',
            'propertyType'            => 'MoneyInterface',
            'objectClass'             => Project::class,
            'comparable'              => true,
            'unit'                    => 'money',
            'predefinedItems'         => null,
        ];
        yield 'field-project_grant' => [
            'fieldAlias'              => 'project_grant',
            'category'                => 'project',
            'type'                    => 'other',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'grant',
            'propertyType'            => 'MoneyInterface',
            'objectClass'             => Project::class,
            'comparable'              => true,
            'unit'                    => 'money',
            'predefinedItems'         => null,
        ];
        yield 'field-land_value' => [
            'fieldAlias'              => 'land_value',
            'category'                => 'project',
            'type'                    => 'other',
            'reservationPropertyName' => 'project',
            'propertyPath'            => 'landValue',
            'propertyType'            => 'MoneyInterface',
            'objectClass'             => Project::class,
            'comparable'              => true,
            'unit'                    => 'money',
            'predefinedItems'         => null,
        ];
        yield 'field-supporting_generations_renewal' => [
            'fieldAlias'              => 'supporting_generations_renewal',
            'category'                => 'loan',
            'type'                    => 'bool',
            'reservationPropertyName' => 'financingObjects',
            'propertyPath'            => 'supportingGenerationsRenewal',
            'propertyType'            => 'bool',
            'objectClass'             => FinancingObject::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-financing_object_type' => [
            'fieldAlias'              => 'financing_object_type',
            'category'                => 'loan',
            'type'                    => 'list',
            'reservationPropertyName' => 'financingObjects',
            'propertyPath'            => 'financingObjectType',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => FinancingObject::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-loan_naf_code' => [
            'fieldAlias'              => 'loan_naf_code',
            'category'                => 'loan',
            'type'                    => 'list',
            'reservationPropertyName' => 'financingObjects',
            'propertyPath'            => 'loanNafCode',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => FinancingObject::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-bfr_value' => [
            'fieldAlias'              => 'bfr_value',
            'category'                => 'loan',
            'type'                    => 'other',
            'reservationPropertyName' => 'financingObjects',
            'propertyPath'            => 'bfrValue',
            'propertyType'            => 'MoneyInterface',
            'objectClass'             => FinancingObject::class,
            'comparable'              => true,
            'unit'                    => 'money',
            'predefinedItems'         => null,
        ];
        yield 'field-loan_type' => [
            'fieldAlias'              => 'loan_type',
            'category'                => 'loan',
            'type'                    => 'list',
            'reservationPropertyName' => 'financingObjects',
            'propertyPath'            => 'loanType',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => FinancingObject::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => ['term_loan', 'short_term', 'revolving_credit', 'stand_by', 'signature_commitment'],
        ];
        yield 'field-loan_duration' => [
            'fieldAlias'              => 'loan_duration',
            'category'                => 'loan',
            'type'                    => 'other',
            'reservationPropertyName' => 'financingObjects',
            'propertyPath'            => 'loanDuration',
            'propertyType'            => 'int',
            'objectClass'             => FinancingObject::class,
            'comparable'              => true,
            'unit'                    => 'month',
            'predefinedItems'         => null,
        ];
        yield 'field-loan_deferral' => [
            'fieldAlias'              => 'loan_deferral',
            'category'                => 'loan',
            'type'                    => 'other',
            'reservationPropertyName' => 'financingObjects',
            'propertyPath'            => 'loanDeferral',
            'propertyType'            => 'int',
            'objectClass'             => FinancingObject::class,
            'comparable'              => true,
            'unit'                    => 'month',
            'predefinedItems'         => null,
        ];
        yield 'field-loan_periodicity' => [
            'fieldAlias'              => 'loan_periodicity',
            'category'                => 'loan',
            'type'                    => 'list',
            'reservationPropertyName' => 'financingObjects',
            'propertyPath'            => 'loanPeriodicity',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => FinancingObject::class,
            'comparable'              => true,
            'unit'                    => 'month',
            'predefinedItems'         => ['monthly', 'quarterly', 'semi_annually', 'annually'],
        ];
        yield 'field-investment_location' => [
            'fieldAlias'              => 'investment_location',
            'category'                => 'loan',
            'type'                    => 'other',
            'reservationPropertyName' => 'financingObjects',
            'propertyPath'            => 'investmentLocation',
            'propertyType'            => 'ProgramChoiceOption',
            'objectClass'             => FinancingObject::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
    }
}
