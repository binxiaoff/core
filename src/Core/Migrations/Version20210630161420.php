<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210630161420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-3900 add Program options and FinancingObject information + update loan_periodicity semi-annually with underscore';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD invoice_money_amount NUMERIC(15, 2) DEFAULT NULL, ADD invoice_money_currency VARCHAR(3) DEFAULT NULL, ADD achievement_money_amount NUMERIC(15, 2) DEFAULT NULL, ADD achievement_money_currency VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_program ADD esb_calculation_activated TINYINT(1) DEFAULT NULL, ADD loan_released_on_invoice TINYINT(1) DEFAULT NULL');

        $loanPeriodicity = \json_encode(['monthly', 'quarterly', 'semi_annually', 'annually']);
        $this->addSql("UPDATE credit_guaranty_field SET predefined_items = '{$loanPeriodicity}' WHERE field_alias = 'loan_periodicity'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_financing_object DROP invoice_money_amount, DROP invoice_money_currency, DROP achievement_money_amount, DROP achievement_money_currency');
        $this->addSql('ALTER TABLE credit_guaranty_program DROP esb_calculation_activated, DROP loan_released_on_invoice');
    }
}
