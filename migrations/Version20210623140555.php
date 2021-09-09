<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210623140555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-3886 CALS-3887 update loan fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'loan_amount'");
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'loan_released_on_invoice'");
        $this->addSql("UPDATE credit_guaranty_field SET field_alias = 'financing_object_type', category = 'loan', property_path = 'financingObjectType' WHERE field_alias = 'financing_object'");
        $this->addSql("UPDATE credit_guaranty_field SET comparable = 1, unit = 'month' WHERE field_alias = 'loan_duration'");
        $this->addSql("UPDATE credit_guaranty_field SET reservation_property_name = 'financingObjects', property_path = 'loanDeferral', object_class = 'Unilend\\\\CreditGuaranty\\\\Entity\\\\FinancingObject' WHERE field_alias = 'loan_deferral'");

        $fields = <<<'INSERT_FIELDS'
            INSERT INTO credit_guaranty_field (public_id, category, type, field_alias, reservation_property_name, property_path, object_class, comparable, unit, predefined_items) VALUES
            ('b7da24f1-4d1c-426e-9e25-ef2773113d2a', 'loan', 'bool', 'supporting_generations_renewal', 'financingObjects', 'supportingGenerationsRenewal', 'Unilend\\CreditGuaranty\\Entity\\FinancingObject', 0, NULL, NULL),
            ('58acfc84-3d39-4a9d-98bd-acd884a0e74b', 'loan', 'list', 'loan_naf_code', 'financingObjects', 'loanNafCode', 'Unilend\\CreditGuaranty\\Entity\\FinancingObject', 0, NULL, NULL),
            ('55a1b77f-1b2d-40a8-8f77-a4fa2ae5f292', 'loan', 'list', 'bfr_value', 'financingObjects', 'bfrValue::amount', 'Unilend\\CreditGuaranty\\Entity\\FinancingObject', 1, 'money', NULL),
            ('93319782-8cfd-474f-bfe8-ab5aae88456b', 'loan', 'list', 'loan_periodicity', 'financingObjects', 'loanPeriodicity', 'Unilend\\CreditGuaranty\\Entity\\FinancingObject', 0, NULL, '["monthly","quarterly","semi-annually","annually"]'),
            ('61ad5da2-1ae1-4c0b-b3bd-ce42fc0bea3b', 'loan', 'list', 'investment_location', 'financingObjects', 'investmentLocation', 'Unilend\\CreditGuaranty\\Entity\\FinancingObject', 0, NULL, NULL);
            INSERT_FIELDS;
        $this->addSql($fields);
    }
}
