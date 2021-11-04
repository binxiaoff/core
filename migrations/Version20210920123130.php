<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210920123130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4449 add tag property in credit_guaranty_field table + hydrate';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_field ADD tag VARCHAR(11) NOT NULL');
        $this->addSql("UPDATE credit_guaranty_field SET tag = 'eligibility' WHERE TRUE");

        $this->addSql(<<<'SQL'
            INSERT IGNORE INTO credit_guaranty_field (
                public_id, tag, category, type, field_alias, reservation_property_name, property_path, property_type, object_class, comparable, unit, predefined_items
            ) VALUES
            ('a11265ea-983c-4c5d-8518-feb163972288', 'info', 'program', 'other', 'program_currency', 'program', 'funds.currency', 'string', 'KLS\\CreditGuaranty\\FEI\\Entity\\Program', 0, NULL, NULL),
            ('dec52b3a-c544-48cc-9c0a-f118d7f056c5', 'info', 'program', 'other', 'guaranty_duration', 'program', 'guarantyDuration', 'int', 'KLS\\CreditGuaranty\\FEI\\Entity\\Program', 0, NULL, NULL),
            ('c1d1f43c-4f1b-4d07-92cd-03e2c898cda9', 'info', 'program', 'bool', 'esb_calculation_activated', 'program', 'esbCalculationActivated', 'bool', 'KLS\\CreditGuaranty\\FEI\\Entity\\Program', 0, NULL, NULL),
            ('cb808233-64ca-40a0-8cbb-a2cb07193325', 'info', 'program', 'bool', 'loan_released_on_invoice', 'program', 'loanReleasedOnInvoice', 'bool', 'KLS\\CreditGuaranty\\FEI\\Entity\\Program', 0, NULL, NULL),
            ('ebdcd939-65bd-4882-8fa9-4afdf933cd22', 'info', 'program', 'other', 'max_fei_credit', 'program', 'maxFeiCredit', 'NullableMoney', 'KLS\\CreditGuaranty\\FEI\\Entity\\Program', 0, NULL, NULL),
            ('53e4fee4-dee8-4620-9a75-85127b139fae', 'info', 'program', 'other', 'rating_model', 'program', 'ratingModel', 'string', 'KLS\\CreditGuaranty\\FEI\\Entity\\Program', 0, NULL, NULL),
            ('798d4095-a599-4e14-b0ad-6e06e5edb55f', 'info', 'reservation', 'other', 'reservation_name', 'name', '', 'string', '', 0, NULL, NULL),
            ('78465f81-b2b0-4fa4-a708-23556a9fda39', 'info', 'reservation', 'other', 'reservation_status', 'currentStatus', '', 'int', '', 0, NULL, NULL),
            ('17faacdb-448a-4a82-befb-ea1b3d5f83a3', 'info', 'reservation', 'other', 'reservation_creation_date', 'added', '', 'DateTimeImmutable', '', 0, NULL, NULL),
            ('d35bcf2a-aaaa-4c97-8eba-e7336589049a', 'info', 'reservation', 'other', 'reservation_refusal_date', 'refusedByManagingCompanyDate', '', 'DateTimeImmutable', '', 0, NULL, NULL),
            ('a7948dc1-eda2-4d92-a4f8-9646b5a0e41d', 'info', 'reservation', 'other', 'reservation_signing_date', 'signingDate', '', 'DateTimeImmutable', '', 0, NULL, NULL),
            ('0fa045c5-e153-4bf4-804c-e257a89a26d5', 'info', 'reservation', 'other', 'reservation_managing_company', 'managingCompany', 'displayName', 'string', 'KLS\\Core\\Entity\\Company', 0, NULL, NULL),
            ('13a46517-8654-4b73-aa67-8b48ce5da334', 'info', 'profile', 'other', 'borrower_type_grade', 'borrower', 'grade', 'string', 'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL),
            ('e42a92b1-9fd0-48a5-b164-e3a7c191f429', 'info', 'project', 'other', 'project_detail', 'project', 'name', 'string', 'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL),
            ('2fc96208-9fbe-40db-a04f-09825dae23a0', 'info', 'loan', 'other', 'financing_object_name', 'financingObjects', 'name', 'string', 'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL),
            ('dc41f2c0-0ca6-4ac4-8d92-c9f583b97923', 'info', 'loan', 'other', 'loan_money', 'financingObjects', 'loanMoney', 'Money', 'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL),
            ('6a5fb99d-60cc-483b-a39f-bb3a7a7825f1', 'info', 'loan', 'bool', 'main_loan', 'financingObjects', 'mainLoan', 'bool', 'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL),
            ('ac666a78-55de-47b2-a7bf-6076661f9385', 'info', 'loan', 'other', 'loan_name', 'financingObjects', 'mainLoan', 'bool', 'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL),
            ('de474438-9ffe-40c5-aedd-6bdb5f145354', 'info', 'loan', 'other', 'loan_number', 'financingObjects', 'loanNumber', 'string', 'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL),
            ('762ca5ca-9266-4f9a-bade-02e04ff5614e', 'info', 'loan', 'other', 'loan_operation_number', 'financingObjects', 'operationNumber', 'string', 'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL),
            ('495c6d49-f87c-40c8-a0d3-2af1cae770fb', 'info', 'loan', 'other', 'first_release_date', 'financingObjects', 'firstReleaseDate', 'DateTimeImmutable', 'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL),
            ('0c16807e-01e9-41b8-aa45-d20d9f7381c3', 'imported', 'loan', 'other', 'loan_new_maturity', 'financingObjects', 'newMaturity', 'int', 'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL),
            ('8d4fc763-4df3-4af2-8bce-21eb11363988', 'imported', 'loan', 'other', 'loan_remaining_capital', 'financingObjects', 'remainingCapital', 'NullableMoney', 'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL),
            ('a9ef5697-5e3f-482c-a42a-2dd52277fd76', 'calcul', 'project', 'other', 'total_gross_subsidy_equivalent', 'project', 'totalGrossSubsidyEquivalent', 'MoneyInterface', 'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL)
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_field DROP tag');
    }
}
