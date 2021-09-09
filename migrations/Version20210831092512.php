<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210831092512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-3652 refactor reservation borrower and project';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_borrower DROP FOREIGN KEY FK_D7ADB78C5ADA84A2');
        $this->addSql('DROP INDEX UNIQ_D7ADB78C5ADA84A2 ON credit_guaranty_borrower');
        $this->addSql('ALTER TABLE credit_guaranty_borrower DROP id_reservation, CHANGE company_name company_name VARCHAR(100) DEFAULT NULL, CHANGE grade grade VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D0255ADA84A2');
        $this->addSql('DROP INDEX UNIQ_A452D0255ADA84A2 ON credit_guaranty_project');
        $this->addSql('ALTER TABLE credit_guaranty_project DROP id_reservation, CHANGE funding_money_amount funding_money_amount NUMERIC(15, 2) DEFAULT NULL, CHANGE funding_money_currency funding_money_currency VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD id_borrower INT NOT NULL, ADD id_project INT NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD CONSTRAINT FK_DE8BB8578B4BA121 FOREIGN KEY (id_borrower) REFERENCES credit_guaranty_borrower (id)');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD CONSTRAINT FK_DE8BB857F12E799E FOREIGN KEY (id_project) REFERENCES credit_guaranty_project (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DE8BB8578B4BA121 ON credit_guaranty_reservation (id_borrower)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DE8BB857F12E799E ON credit_guaranty_reservation (id_project)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD id_reservation INT NOT NULL, CHANGE company_name company_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE grade grade VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD CONSTRAINT FK_D7ADB78C5ADA84A2 FOREIGN KEY (id_reservation) REFERENCES credit_guaranty_reservation (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D7ADB78C5ADA84A2 ON credit_guaranty_borrower (id_reservation)');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD id_reservation INT NOT NULL, CHANGE funding_money_amount funding_money_amount NUMERIC(15, 2) NOT NULL, CHANGE funding_money_currency funding_money_currency VARCHAR(3) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D0255ADA84A2 FOREIGN KEY (id_reservation) REFERENCES credit_guaranty_reservation (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A452D0255ADA84A2 ON credit_guaranty_project (id_reservation)');
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP FOREIGN KEY FK_DE8BB8578B4BA121');
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP FOREIGN KEY FK_DE8BB857F12E799E');
        $this->addSql('DROP INDEX UNIQ_DE8BB8578B4BA121 ON credit_guaranty_reservation');
        $this->addSql('DROP INDEX UNIQ_DE8BB857F12E799E ON credit_guaranty_reservation');
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP id_borrower, DROP id_project');
    }
}
