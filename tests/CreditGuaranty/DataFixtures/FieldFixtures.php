<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\Test\Core\DataFixtures\AbstractFixtures;

class FieldFixtures extends AbstractFixtures
{
    public function load(ObjectManager $manager)
    {
        foreach ($this->loadData() as $reference => $fieldData) {
            $field = new Field(
                $fieldData['fieldAlias'],
                $fieldData['category'],
                $fieldData['type'],
                $fieldData['targetPropertyAccessPath'],
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
        yield 'field-creation_in_progress' => [
            'fieldAlias'               => 'creation_in_progress',
            'category'                 => 'general',
            'type'                     => 'bool',
            'targetPropertyAccessPath' => 'borrower::creationInProgress',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-receiving_grant' => [
            'fieldAlias'               => 'receiving_grant',
            'category'                 => 'general',
            'type'                     => 'bool',
            'targetPropertyAccessPath' => 'borrowerBusinessActivity::receivingGrant',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-subsidiary' => [
            'fieldAlias'               => 'subsidiary',
            'category'                 => 'general',
            'type'                     => 'bool',
            'targetPropertyAccessPath' => 'borrowerBusinessActivity::subsidiary',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-borrower_type' => [
            'fieldAlias'               => 'borrower_type',
            'category'                 => 'profile',
            'type'                     => 'list',
            'targetPropertyAccessPath' => 'borrower::borrowerType',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-company_name' => [
            'fieldAlias'               => 'company_name',
            'category'                 => 'profile',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrower::companyName',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-company_address' => [
            'fieldAlias'               => 'company_address',
            'category'                 => 'profile',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrowerBusinessActivity::address',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-beneficiary_name' => [
            'fieldAlias'               => 'beneficiary_name',
            'category'                 => 'profile',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrower::beneficiaryName',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-beneficiary_address' => [
            'fieldAlias'               => 'beneficiary_address',
            'category'                 => 'profile',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrower::address',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-tax_number' => [
            'fieldAlias'               => 'tax_number',
            'category'                 => 'profile',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrower::taxNumber',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-legal_form' => [
            'fieldAlias'               => 'legal_form',
            'category'                 => 'profile',
            'type'                     => 'list',
            'targetPropertyAccessPath' => 'borrower::legalForm',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => ['SARL', 'SAS', 'SASU', 'EURL', 'SA', 'SELAS'],
        ];
        yield 'field-siret' => [
            'fieldAlias'               => 'siret',
            'category'                 => 'activity',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrowerBusinessActivity::siret',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-activity_country' => [
            'fieldAlias'               => 'activity_country',
            'category'                 => 'activity',
            'type'                     => 'list',
            'targetPropertyAccessPath' => 'borrowerBusinessActivity::address::country',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => ['FR'],
        ];
        yield 'field-employees_number' => [
            'fieldAlias'               => 'employees_number',
            'category'                 => 'activity',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrowerBusinessActivity::employeesNumber',
            'comparable'               => true,
            'unit'                     => 'person',
            'predefinedItems'          => null,
        ];
        yield 'field-last_year_turnover' => [
            'fieldAlias'               => 'last_year_turnover',
            'category'                 => 'activity',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrowerBusinessActivity::lastYearTurnover',
            'comparable'               => true,
            'unit'                     => 'money',
            'predefinedItems'          => null,
        ];
        yield 'field-5_years_average_turnover' => [
            'fieldAlias'               => '5_years_average_turnover',
            'category'                 => 'activity',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrowerBusinessActivity::fiveYearsAverageTurnover',
            'comparable'               => true,
            'unit'                     => 'money',
            'predefinedItems'          => null,
        ];
        yield 'field-total_assets' => [
            'fieldAlias'               => 'total_assets',
            'category'                 => 'activity',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrowerBusinessActivity::totalAssets',
            'comparable'               => true,
            'unit'                     => 'money',
            'predefinedItems'          => null,
        ];
        yield 'field-grant_amount' => [
            'fieldAlias'               => 'grant_amount',
            'category'                 => 'activity',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrowerBusinessActivity::grant',
            'comparable'               => true,
            'unit'                     => 'money',
            'predefinedItems'          => null,
        ];
        yield 'field-investment_thematic' => [
            'fieldAlias'               => 'investment_thematic',
            'category'                 => 'project',
            'type'                     => 'list',
            'targetPropertyAccessPath' => 'project::investmentThematic',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-project_total_amount' => [
            'fieldAlias'               => 'project_total_amount',
            'category'                 => 'project',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'project::fundingMoney',
            'comparable'               => true,
            'unit'                     => 'money',
            'predefinedItems'          => null,
        ];
        yield 'field-naf_code_project' => [
            'fieldAlias'               => 'naf_code_project',
            'category'                 => 'project',
            'type'                     => 'list',
            'targetPropertyAccessPath' => 'project::nafNace::nafCode',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-financing_object' => [
            'fieldAlias'               => 'financing_object',
            'category'                 => 'project',
            'type'                     => 'list',
            'targetPropertyAccessPath' => 'financingObjects::financingObject',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-loan_type' => [
            'fieldAlias'               => 'loan_type',
            'category'                 => 'loan',
            'type'                     => 'list',
            'targetPropertyAccessPath' => 'financingObjects::loanType',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => ['term_loan', 'short_term', 'revolving_credit', 'stand_by', 'signature_commitment'],
        ];
        yield 'field-loan_duration' => [
            'fieldAlias'               => 'loan_duration',
            'category'                 => 'loan',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'financingObjects::loanDuration',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-loan_released_on_invoice' => [
            'fieldAlias'               => 'loan_released_on_invoice',
            'category'                 => 'loan',
            'type'                     => 'bool',
            'targetPropertyAccessPath' => 'financingObjects::releasedOnInvoice',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-loan_amount' => [
            'fieldAlias'               => 'loan_amount',
            'category'                 => 'loan',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'financingObjects::loanMoney',
            'comparable'               => true,
            'unit'                     => 'money',
            'predefinedItems'          => null,
        ];
    }
}
