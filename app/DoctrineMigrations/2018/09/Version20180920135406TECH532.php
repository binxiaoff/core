<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180920135406TECH532 extends AbstractMigration
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
        $this->addSql(<<<'ALTERPROJECTS'
ALTER TABLE projects
  DROP KEY status,
  DROP FOREIGN KEY projects_ibfk_1,
  ADD KEY fk_projects_status (status),
  ADD CONSTRAINT fk_projects_status FOREIGN KEY (status) REFERENCES projects_status (status) ON UPDATE CASCADE
ALTERPROJECTS
        );
        $this->addSql(<<<'CREATEVIEWSEARCHPROJECT'
CREATE VIEW project_search AS SELECT p.id_project, co.sector,
CASE WHEN p.status = 50
  THEN UNIX_TIMESTAMP() / UNIX_TIMESTAMP(p.date_retrait)
  ELSE - UNIX_TIMESTAMP() / UNIX_TIMESTAMP(p.date_fin)
END AS sortDate,
IFNULL(CASE
  WHEN p.status IN (40, 45, 50, 75) THEN (SELECT SUM(amount * rate) / SUM(amount) AS avg_rate FROM bids WHERE id_project = p.id_project AND status IN (0, 1))
  WHEN p.status = 70 THEN (SELECT SUM(amount * rate) / SUM(amount) AS avg_rate FROM bids WHERE id_project = p.id_project)
  WHEN p.status >= 60 THEN (SELECT SUM(amount * rate) / SUM(amount) AS avg_rate FROM loans WHERE id_project = p.id_project)
  ELSE 0
END, 0) AS avgRate
FROM projects p FORCE INDEX (fk_projects_status)
INNER JOIN companies co ON p.id_company = co.id_company
WHERE p.status >= 40
CREATEVIEWSEARCHPROJECT
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
        $this->addSql(<<<'ALTERPROJECTS'
ALTER TABLE projects
  DROP KEY fk_projects_status,
  DROP FOREIGN KEY fk_projects_status,
  ADD KEY status (status),
  ADD CONSTRAINT projects_ibfk_1 FOREIGN KEY (status) REFERENCES projects_status (status) ON UPDATE CASCADE
ALTERPROJECTS
        );
        $this->addSql('DROP VIEW project_search');
    }
}
