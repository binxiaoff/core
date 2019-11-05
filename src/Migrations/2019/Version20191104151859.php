<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191104151859 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-486 (Upload attachment to existing projects)';
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE attachment CHANGE archived archived DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_61F9A289F12E799EDCD5596C ON project_attachment (id_project, id_attachment)');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_61F9A289F12E799EDCD5596C ON project_attachment');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_61F9A289464E68B ON project_attachment (attachment_id)');
    }
}
