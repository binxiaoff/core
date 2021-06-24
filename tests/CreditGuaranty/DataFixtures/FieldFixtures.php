<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Unilend\CreditGuaranty\Entity\Borrower;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\FinancingObject;
use Unilend\CreditGuaranty\Entity\Project;
use Unilend\Test\Core\DataFixtures\AbstractFixtures;

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
            'propertyPath'            => 'turnover::amount',
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
            'propertyPath'            => 'totalAssets::amount',
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
            'propertyPath'            => 'fundingMoney::amount',
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
            'propertyPath'            => 'contribution::amount',
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
            'propertyPath'            => 'eligibleFeiCredit::amount',
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
            'propertyPath'            => 'totalFeiCredit::amount',
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
            'propertyPath'            => 'tangibleFeiCredit::amount',
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
            'propertyPath'            => 'intangibleFeiCredit::amount',
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
            'propertyPath'            => 'creditExcludingFei::amount',
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
            'propertyPath'            => 'grant::amount',
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
            'propertyPath'            => 'landValue::amount',
            'objectClass'             => Project::class,
            'comparable'              => true,
            'unit'                    => 'money',
            'predefinedItems'         => null,
        ];
        yield 'field-financing_object' => [
            'fieldAlias'              => 'financing_object',
            'category'                => 'project',
            'type'                    => 'list',
            'reservationPropertyName' => 'financingObjects',
            'propertyPath'            => 'financingObject',
            'objectClass'             => Project::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-loan_released_on_invoice' => [
            'fieldAlias'              => 'loan_released_on_invoice',
            'category'                => 'loan',
            'type'                    => 'bool',
            'reservationPropertyName' => 'financingObjects',
            'propertyPath'            => 'releasedOnInvoice',
            'objectClass'             => FinancingObject::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-loan_type' => [
            'fieldAlias'              => 'loan_type',
            'category'                => 'loan',
            'type'                    => 'list',
            'reservationPropertyName' => 'financingObjects',
            'propertyPath'            => 'loanType',
            'objectClass'             => FinancingObject::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => ['term_loan', 'short_term', 'revolving_credit', 'stand_by', 'signature_commitment'],
        ];
        yield 'field-loan_amount' => [
            'fieldAlias'              => 'loan_amount',
            'category'                => 'loan',
            'type'                    => 'other',
            'reservationPropertyName' => 'financingObjects',
            'propertyPath'            => 'loanMoney::amount',
            'objectClass'             => FinancingObject::class,
            'comparable'              => true,
            'unit'                    => 'money',
            'predefinedItems'         => null,
        ];
        yield 'field-loan_duration' => [
            'fieldAlias'              => 'loan_duration',
            'category'                => 'loan',
            'type'                    => 'other',
            'reservationPropertyName' => 'financingObjects',
            'propertyPath'            => 'loanDuration',
            'objectClass'             => FinancingObject::class,
            'comparable'              => false,
            'unit'                    => null,
            'predefinedItems'         => null,
        ];
        yield 'field-loan_deferral' => [
            'fieldAlias'              => 'loan_deferral',
            'category'                => 'loan',
            'type'                    => 'other',
            'reservationPropertyName' => '',
            'propertyPath'            => '',
            'objectClass'             => '',
            'comparable'              => true,
            'unit'                    => 'month',
            'predefinedItems'         => null,
        ];
    }
}
