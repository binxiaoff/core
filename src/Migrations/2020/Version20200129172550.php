<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200129172550 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-820 Update company status indexes';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_company_status_status ON company_status');
        $this->addSql('ALTER TABLE company_status RENAME INDEX idx_company_status_id_client TO IDX_469F01699122A03F');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_company_status_status ON company_status (status)');
        $this->addSql('ALTER TABLE company_status RENAME INDEX idx_469f01699122a03f TO idx_company_status_id_client');
    }
}
