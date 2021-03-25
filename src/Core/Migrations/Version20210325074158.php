<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210325074158 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '[Agency] Add index on added field';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE INDEX IDX_A804ADB2CBBF90EB ON agency_term_history (added)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('DROP INDEX IDX_A804ADB2CBBF90EB ON agency_term_history');
    }
}
