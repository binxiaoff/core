<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionProjectsCommentsIdClient extends AbstractMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql(<<<ALTERTABLE
ALTER TABLE projects_comments
  ADD COLUMN id_client INT(11) NOT NULL AFTER id_project,
  ADD CONSTRAINT fk_projects_comments_id_client FOREIGN KEY (id_client) REFERENCES clients (id_client)
ALTERTABLE
        );
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql(<<<ALTERTABLE
ALTER TABLE projects_comments
  DROP COLUMN id_client
ALTERTABLE
        );
    }
}
