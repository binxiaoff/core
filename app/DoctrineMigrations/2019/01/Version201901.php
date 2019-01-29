<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version201901 extends AbstractMigration
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

        $this->addSql('ALTER TABLE projects DROP COLUMN id_prescripteur');

        $this->addSql('ALTER TABLE temporary_links_login CHANGE accessed accessed DATETIME');
        $this->addSql('ALTER TABLE temporary_links_login CHANGE updated updated DATETIME');
        $this->addSql('ALTER TABLE temporary_links_login DROP KEY id_link');
        $this->addSql('ALTER TABLE temporary_links_login ADD CONSTRAINT fk_temporary_links_login_id_client FOREIGN KEY (id_client) REFERENCES clients (id_client)');
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

        $this->addSql('ALTER TABLE projects ADD COLUMN id_prescripteur INT(11) DEFAULT NULL AFTER id_target_company');
        $this->addSql('ALTER TABLE projects ADD KEY id_prescripteur (id_prescripteur)');

        $this->addSql('ALTER TABLE temporary_links_login CHANGE accessed accessed DATETIME NOT NULL');
        $this->addSql('ALTER TABLE temporary_links_login CHANGE updated updated DATETIME NOT NULL');
        $this->addSql('ALTER TABLE temporary_links_login DROP FOREIGN KEY fk_temporary_links_login_id_client');
        $this->addSql('ALTER TABLE temporary_links_login DROP KEY fk_temporary_links_login_id_client');
        $this->addSql('ALTER TABLE temporary_links_login ADD CONSTRAINT id_link UNIQUE (id_link)');
    }
}
