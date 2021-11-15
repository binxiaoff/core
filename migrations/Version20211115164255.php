<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211115164255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Core] CALS-5097 make some properties nullable in core_company table + transfer bank_code values to client_number';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_company ADD client_number VARCHAR(10) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5DA8BC7CC33654E7 ON core_company (client_number)');
        $this->addSql('UPDATE core_company c SET c.client_number = c.bank_code');
        $this->addSql('DROP INDEX UNIQ_5DA8BC7CDD756216 ON core_company');
        $this->addSql('ALTER TABLE core_company DROP bank_code, CHANGE legal_name legal_name VARCHAR(300) DEFAULT NULL, CHANGE applicable_vat applicable_vat VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_company ADD bank_code VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5DA8BC7CDD756216 ON core_company (bank_code)');
        $this->addSql('UPDATE core_company c SET c.bank_code = c.client_number');
        $this->addSql('DROP INDEX UNIQ_5DA8BC7CC33654E7 ON core_company');
        $this->addSql('ALTER TABLE core_company DROP client_number, CHANGE legal_name legal_name VARCHAR(300) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE applicable_vat applicable_vat VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
