<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionDropProspects extends AbstractMigration
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

        $this->addSql('DROP TABLE prospects');
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

        $this->addSql(<<<CREATETABLE
CREATE TABLE prospects (
  id_prospect INT(11) NOT NULL AUTO_INCREMENT,
  nom MEDIUMTEXT NOT NULL,
  prenom MEDIUMTEXT NOT NULL,
  email VARCHAR(191) NOT NULL,
  id_langue VARCHAR(3) NOT NULL,
  source VARCHAR(191) NOT NULL,
  source2 VARCHAR(191) NOT NULL,
  source3 VARCHAR(191) NOT NULL,
  slug_origine VARCHAR(191) NOT NULL,
  added DATETIME NOT NULL,
  updated DATETIME NOT NULL,
  PRIMARY KEY (id_prospect),
  KEY idx_prospects_email (email)
)
CREATETABLE
        );
    }
}
