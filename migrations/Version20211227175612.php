<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211227175612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-5453 CALS-5455 Rename BankAccount::bankInstitution into BankAccount::institution';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent CHANGE bank_account_bank_institution bank_account_institution VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_borrower CHANGE bank_account_bank_institution bank_account_institution VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_participation CHANGE bank_account_bank_institution bank_account_institution VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent CHANGE bank_account_institution bank_account_bank_institution VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agency_borrower CHANGE bank_account_institution bank_account_bank_institution VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agency_participation CHANGE bank_account_institution bank_account_bank_institution VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
