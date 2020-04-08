<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200407200411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1311 add public id for project_file';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_file ADD public_id VARCHAR(36) NOT NULL');
        $this->addSql('UPDATE project_file set public_id = UUID()');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B50EFE08B5B48B91 ON project_file (public_id)');
        $this->addSql('ALTER TABLE file CHANGE archived archived DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE file CHANGE archived archived DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX UNIQ_B50EFE08B5B48B91 ON project_file');
        $this->addSql('ALTER TABLE project_file DROP public_id');
    }
}
