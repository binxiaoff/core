<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211227175805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-5453 CALS-5455 Rename BankAccount properties';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent CHANGE bank_account_name bank_account_label VARCHAR(255) DEFAULT NULL, CHANGE bank_account_institution bank_account_institution_name VARCHAR(255) DEFAULT NULL, CHANGE bank_account_address bank_account_institution_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_borrower CHANGE bank_account_name bank_account_label VARCHAR(255) DEFAULT NULL, CHANGE bank_account_institution bank_account_institution_name VARCHAR(255) DEFAULT NULL, CHANGE bank_account_address bank_account_institution_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_participation CHANGE bank_account_name bank_account_label VARCHAR(255) DEFAULT NULL, CHANGE bank_account_institution bank_account_institution_name VARCHAR(255) DEFAULT NULL, CHANGE bank_account_address bank_account_institution_address VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent CHANGE bank_account_label bank_account_name VARCHAR(255) DEFAULT NULL, CHANGE bank_account_institution_name bank_account_institution VARCHAR(255) DEFAULT NULL, CHANGE bank_account_institution_address bank_account_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_borrower CHANGE bank_account_label bank_account_name VARCHAR(255) DEFAULT NULL, CHANGE bank_account_institution_name bank_account_institution VARCHAR(255) DEFAULT NULL, CHANGE bank_account_institution_address bank_account_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_participation CHANGE bank_account_label bank_account_name VARCHAR(255) DEFAULT NULL, CHANGE bank_account_institution_name bank_account_institution VARCHAR(255) DEFAULT NULL, CHANGE bank_account_institution_address bank_account_address VARCHAR(255) DEFAULT NULL');
    }
}
