<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210712124038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-3888 add fields in credit_guaranty_reservation and credit_guaranty_financing_object tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD name VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE credit_guaranty_financing_object
            ADD loan_number VARCHAR(50) DEFAULT NULL,
            ADD operation_number VARCHAR(50) DEFAULT NULL,
            ADD new_maturity SMALLINT DEFAULT NULL,
            ADD remaining_capital_amount NUMERIC(15, 2) DEFAULT NULL,
            ADD remaining_capital_currency VARCHAR(3) DEFAULT NULL,
            DROP invoice_money_amount,
            DROP invoice_money_currency,
            DROP achievement_money_amount,
            DROP achievement_money_currency
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP name');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object DROP loan_number, DROP operation_number, DROP new_maturity, DROP remaining_capital_amount, DROP remaining_capital_currency, ADD invoice_money_amount NUMERIC(15, 2) DEFAULT NULL, ADD invoice_money_currency VARCHAR(3) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD achievement_money_amount NUMERIC(15, 2) DEFAULT NULL, ADD achievement_money_currency VARCHAR(3) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
