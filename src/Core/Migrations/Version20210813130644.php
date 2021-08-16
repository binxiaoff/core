<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210813130644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency][Core] Fix some names in database';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_covenant RENAME COLUMN startDate TO start_date, RENAME COLUMN endDate TO end_date');
        $this->addSql('ALTER TABLE core_company_admin RENAME INDEX uniq_companyadmin_companu_user TO uniq_companyAdmin_company_user');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_covenant RENAME COLUMN start_date TO startDate, RENAME COLUMN end_date TO endDate');
        $this->addSql('ALTER TABLE core_company_admin RENAME INDEX uniq_companyadmin_company_user TO uniq_companyAdmin_companu_user');
    }
}
