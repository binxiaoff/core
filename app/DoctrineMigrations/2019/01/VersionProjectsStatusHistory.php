<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionProjectsStatusHistory extends AbstractMigration
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
ALTER TABLE projects_status_history
  CHANGE content content MEDIUMTEXT,
  CHANGE numero_relance numero_relance INT(11),
  CHANGE updated updated DATETIME
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
ALTER TABLE projects_status_history
  CHANGE content content MEDIUMTEXT NOT NULL,
  CHANGE numero_relance numero_relance INT(11) NOT NULL,
  CHANGE updated updated DATETIME NOT NULL
ALTERTABLE
        );
    }
}
