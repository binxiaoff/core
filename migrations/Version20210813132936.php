<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210813132936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Core] CALS-4252 Rename Company::companyName into Company::legalName';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_company CHANGE company_name legal_name VARCHAR(300)  CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_company CHANGE legal_name company_name VARCHAR(300) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
