<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionUsers extends AbstractMigration
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
ALTER TABLE users
  CHANGE email email VARCHAR(191) NOT NULL AFTER id_user_type,
  CHANGE phone phone VARCHAR(50),
  CHANGE mobile mobile VARCHAR(50),
  CHANGE slack slack VARCHAR(191),
  CHANGE password_edited password_edited DATETIME,
  CHANGE updated updated DATETIME,
  CHANGE lastlogin lastlogin DATETIME
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
ALTER TABLE users
  CHANGE email email VARCHAR(191) NOT NULL AFTER name,
  CHANGE phone phone VARCHAR(50) NOT NULL,
  CHANGE mobile mobile VARCHAR(50) NOT NULL,
  CHANGE slack slack VARCHAR(191) NOT NULL,
  CHANGE password_edited password_edited DATETIME NOT NULL,
  CHANGE updated updated DATETIME NOT NULL,
  CHANGE lastlogin lastlogin DATETIME NOT NULL
ALTERTABLE
        );
    }
}
