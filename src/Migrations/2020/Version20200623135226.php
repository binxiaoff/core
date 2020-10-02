<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200623135226 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1361 Remove confidential';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP confidential');
        $this->addSql('ALTER TABLE project_participation_contact CHANGE confidentiality_accepted nda_accepted DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD confidential TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE project_participation_contact CHANGE nda_accepted confidentiality_accepted DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
