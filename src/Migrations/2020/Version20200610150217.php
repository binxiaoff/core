<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200610150217 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1534 Add new fields to Tranche';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tranche ADD syndicated TINYINT(1) NOT NULL, ADD unsyndicated_funder_type VARCHAR(255) DEFAULT NULL, ADD third_party_funder VARCHAR(255) DEFAULT NULL, ADD color VARCHAR(255) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tranche DROP syndicated, DROP unsyndicated_funder_type, DROP third_party_funder, DROP color');
    }
}
