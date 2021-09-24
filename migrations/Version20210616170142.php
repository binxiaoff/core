<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210616170142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-3931 split target_property_access_path + change address fields + update fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_field 
            ADD reservation_property_name VARCHAR(255) NOT NULL, 
            ADD property_path VARCHAR(255) NOT NULL,
            ADD object_class VARCHAR(255) NOT NULL,
            DROP target_property_access_path');

        $this->addSql('ALTER TABLE credit_guaranty_borrower 
            ADD address_street VARCHAR(100) DEFAULT NULL,
            ADD id_address_country INT DEFAULT NULL,
            DROP address_country,
            DROP address_road_number,
            DROP address_road_name');

        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD CONSTRAINT FK_D7ADB78CE1C8FD76 FOREIGN KEY (id_address_country) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('CREATE INDEX IDX_D7ADB78CE1C8FD76 ON credit_guaranty_borrower (id_address_country)');

        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'beneficiaryName', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'beneficiary_name'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'borrowerType', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'borrower_type'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'youngFarmer', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'young_farmer'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'creationInProgress', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'creation_in_progress'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'subsidiary', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'subsidiary'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'companyName', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'company_name'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'addressCountry', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'activity_country'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'activityStartDate', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'activity_start_date'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'siret', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'siret'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'taxNumber', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'tax_number'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'legalForm', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'legal_form'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'companyNafCode', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'company_naf_code'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'employeesNumber', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'employees_number'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'exploitationSize', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'exploitation_size'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'turnover::amount', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'turnover'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'borrower', property_path = 'totalAssets::amount', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Borrower' WHERE field_alias = 'total_assets'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'project', property_path = 'investmentThematic', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Project' WHERE field_alias = 'investment_thematic'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'project', property_path = 'fundingMoney::amount', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Project' WHERE field_alias = 'project_total_amount'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'project', property_path = 'projectNafCode', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\Project' WHERE field_alias = 'project_naf_code'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'financingObjects', property_path = 'financingObject', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\FinancingObject' WHERE field_alias = 'financing_object'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'financingObjects', property_path = 'releasedOnInvoice', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\FinancingObject' WHERE field_alias = 'loan_released_on_invoice'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'financingObjects', property_path = 'loanType', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\FinancingObject' WHERE field_alias = 'loan_type'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'financingObjects', property_path = 'loanMoney::amount', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\FinancingObject' WHERE field_alias = 'loan_amount'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'financingObjects', property_path = 'loanDuration', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\FinancingObject' WHERE field_alias = 'loan_duration'");
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'beneficiary_address'");

        $fields = <<<'INSERT_FIELDS'
            INSERT INTO credit_guaranty_field (public_id, category, type, field_alias, reservation_property_name, property_path, object_class, comparable, unit, predefined_items) VALUES
            ('7518eade-0825-4464-8b07-c372fd69300c', 'profile', 'other', 'activity_street', 'borrower', 'addressStreet', 'Unilend\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
            ('ffa27a62-e831-4d9f-bdda-d7dbdd5ab57f', 'profile', 'other', 'activity_post_code', 'borrower', 'addressPostCode', 'Unilend\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
            ('e5c18fa8-adb2-4123-ad49-d7c6b95eae70', 'profile', 'other', 'activity_city', 'borrower', 'addressCity', 'Unilend\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
            ('c4876798-0ed5-4808-9ef9-c1810b158c4f', 'profile', 'other', 'activity_department', 'borrower', 'addressDepartment', 'Unilend\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL);
            INSERT_FIELDS;
        $this->addSql($fields);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_field ADD target_property_access_path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP reservation_property_name, DROP property_path, DROP object_class');
    }
}
