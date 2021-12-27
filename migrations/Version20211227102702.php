<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211227102702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-5453 CALS-5455 Create embeddable for bankAccount';
    }

    public function up(Schema $schema): void
    {
        foreach (['agency_agent', 'agency_borrower', 'agency_participation'] as $tableName) {
            $this->addSql(<<<SQL
ALTER TABLE $tableName 
  ADD bank_account_name VARCHAR(255) DEFAULT NULL,
  CHANGE bank_institution bank_account_bank_institution VARCHAR(255) DEFAULT NULL, 
  CHANGE bank_address bank_account_bank_address VARCHAR(255) DEFAULT NULL, 
  CHANGE bic bank_account_bic VARCHAR(11) DEFAULT NULL, 
  CHANGE iban bank_account_iban VARCHAR(34) DEFAULT NULL
SQL
);

        }
    }

    public function down(Schema $schema): void
    {
        foreach (['agency_agent', 'agency_borrower', 'agency_participation'] as $tableName) {
            $this->addSql(<<<SQL
ALTER TABLE $tableName 
  DROP bank_account_name,
  CHANGE bank_account_bank_institution bank_institution VARCHAR(255) DEFAULT NULL, 
  CHANGE bank_account_bank_address bank_address VARCHAR(255) DEFAULT NULL, 
  CHANGE bank_account_bic bic VARCHAR(11) DEFAULT NULL, 
  CHANGE bank_account_iban iban VARCHAR(34) DEFAULT NULL
SQL
            );

        }
    }
}
