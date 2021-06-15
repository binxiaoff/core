<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Unilend\CreditGuaranty\Entity\Field;
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
        yield 'field-beneficiary_name' => [
            'fieldAlias'               => 'beneficiary_name',
            'category'                 => 'profile',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrower::beneficiaryName',
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
        yield 'field-young_farmer' => [
            'fieldAlias'               => 'young_farmer',
            'category'                 => 'profile',
            'type'                     => 'bool',
            'targetPropertyAccessPath' => 'borrower::youngFarmer',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-creation_in_progress' => [
            'fieldAlias'               => 'creation_in_progress',
            'category'                 => 'profile',
            'type'                     => 'bool',
            'targetPropertyAccessPath' => 'borrower::creationInProgress',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-subsidiary' => [
            'fieldAlias'               => 'subsidiary',
            'category'                 => 'profile',
            'type'                     => 'bool',
            'targetPropertyAccessPath' => 'borrower::subsidiary',
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
        yield 'field-beneficiary_address' => [
            'fieldAlias'               => 'beneficiary_address',
            'category'                 => 'profile',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrower::address',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-activity_country' => [
            'fieldAlias'               => 'activity_country',
            'category'                 => 'profile',
            'type'                     => 'list',
            'targetPropertyAccessPath' => 'borrower::address::country',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => ['FR'],
        ];
        yield 'field-activity_start_date' => [
            'fieldAlias'               => 'activity_start_date',
            'category'                 => 'profile',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrower::activityStartDate',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-siret' => [
            'fieldAlias'               => 'siret',
            'category'                 => 'profile',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrower::siret',
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
        yield 'field-company_naf_code' => [
            'fieldAlias'               => 'company_naf_code',
            'category'                 => 'profile',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrower::companyNafCode',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-employees_number' => [
            'fieldAlias'               => 'employees_number',
            'category'                 => 'profile',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrower::employeesNumber',
            'comparable'               => true,
            'unit'                     => 'person',
            'predefinedItems'          => null,
        ];
        yield 'field-exploitation_size' => [
            'fieldAlias'               => 'exploitation_size',
            'category'                 => 'profile',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrower::exploitationSize',
            'comparable'               => false,
            'unit'                     => null,
            'predefinedItems'          => null,
        ];
        yield 'field-turnover' => [
            'fieldAlias'               => 'turnover',
            'category'                 => 'profile',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrower::turnover::amount',
            'comparable'               => true,
            'unit'                     => 'money',
            'predefinedItems'          => null,
        ];
        yield 'field-total_assets' => [
            'fieldAlias'               => 'total_assets',
            'category'                 => 'profile',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'borrower::totalAssets::amount',
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
            'targetPropertyAccessPath' => 'project::fundingMoney::amount',
            'comparable'               => true,
            'unit'                     => 'money',
            'predefinedItems'          => null,
        ];
        yield 'field-project_naf_code' => [
            'fieldAlias'               => 'project_naf_code',
            'category'                 => 'project',
            'type'                     => 'list',
            'targetPropertyAccessPath' => 'project::projectNafCode',
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
        yield 'field-loan_released_on_invoice' => [
            'fieldAlias'               => 'loan_released_on_invoice',
            'category'                 => 'loan',
            'type'                     => 'bool',
            'targetPropertyAccessPath' => 'financingObjects::releasedOnInvoice',
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
        yield 'field-loan_amount' => [
            'fieldAlias'               => 'loan_amount',
            'category'                 => 'loan',
            'type'                     => 'other',
            'targetPropertyAccessPath' => 'financingObjects::loanMoney::amount',
            'comparable'               => true,
            'unit'                     => 'money',
            'predefinedItems'          => null,
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
    }
}
