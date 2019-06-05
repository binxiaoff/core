<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190515154415 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-101 Add project operation type column';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project ADD operation_type SMALLINT DEFAULT NULL AFTER id_project_status_history');
        $this->addSql('UPDATE project set operation_type = 1');
        $this->addSql('ALTER TABLE project MODIFY operation_type SMALLINT NOT NULL');

        $this->addSql(
            <<<'TRANSLATION'
INSERT INTO translations (locale, section, name, translation, added)
VALUES
  ('fr_FR', 'project-creation', 'debt-arrangement', 'Arrangement de dette<br><small>pour un client</small>', NOW()),
  ('fr_FR', 'project-creation', 'syndication', 'Syndication<br><small>d’un encours client</small>', NOW())
TRANSLATION
        );
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project DROP operation_type');
    }
}
