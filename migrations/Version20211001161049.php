<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211001161049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] add loan_money_after_contractualisation fields in credit_guaranty_financing_object table + add field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD loan_money_after_contractualisation_amount NUMERIC(15, 2) DEFAULT NULL, ADD loan_money_after_contractualisation_currency VARCHAR(3) DEFAULT NULL');
        $this->addSql("INSERT IGNORE INTO credit_guaranty_field (public_id, tag, category, type, field_alias, reservation_property_name, property_path, property_type, object_class, comparable, unit, predefined_items) VALUES ('91c636cc-cb6a-48f2-ad4a-589b588ad441', 'info', 'loan', 'other', 'loan_money_after_contractualisation', 'financingObjects', 'loanMoneyAfterContractualisation', 'NullableMoney', 'KLS\\\\CreditGuaranty\\\\FEI\\\\Entity\\\\FinancingObject', 0, NULL, NULL)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_financing_object DROP loan_money_after_contractualisation_amount, DROP loan_money_after_contractualisation_currency');
    }
}
