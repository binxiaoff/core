<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190424091944 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Update project workflow';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('UPDATE projects SET status = 10 WHERE status = 20');
        $this->addSql('UPDATE projects SET status = 20 WHERE status = 30');
        $this->addSql('UPDATE projects SET status = 30 WHERE status = 40');
        $this->addSql('UPDATE projects SET status = 50 WHERE status = 60');
        $this->addSql('UPDATE projects SET status = 60 WHERE status = 70');
        $this->addSql('UPDATE projects SET status = 70 WHERE status = 80');
        $this->addSql('UPDATE projects_status_history SET id_project_status = 1 WHERE id_project_status = 2');
        $this->addSql('UPDATE projects_status_history SET id_project_status = 2 WHERE id_project_status = 3');
        $this->addSql('UPDATE projects_status_history SET id_project_status = 3 WHERE id_project_status = 4');
        $this->addSql('UPDATE projects_status_history SET id_project_status = 5 WHERE id_project_status = 6');
        $this->addSql('UPDATE projects_status_history SET id_project_status = 6 WHERE id_project_status = 7');
        $this->addSql('UPDATE projects_status_history SET id_project_status = 7 WHERE id_project_status = 8');
        $this->addSql('TRUNCATE projects_status');
        $this->addSql(
            <<<'INSERT'
INSERT INTO projects_status (id_project_status, label, status)
VALUES
  (1, 'Dépôt', 10),
  (2, 'Financement', 20),
  (3, 'Contractualisation', 30),
  (4, 'Signature', 40),
  (5, 'Remboursement', 50),
  (6, 'Clôture', 60),
  (7, 'Perte', 70),
  (8, 'Annulé', 100)
INSERT
        );
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');

        $this->addSql(
            <<<'TRANSLATIONS'
INSERT INTO translations (locale, section, name, translation, added, updated)
VALUES
  ('fr_FR', 'projects-listing', 'pagination-info-location', '%item% sur %items%', NOW(), NOW()),
  ('fr_FR', 'projects-listing', 'pagination-info-next-label', 'Voir %items% suivants', NOW(), NOW()),
  ('fr_FR', 'projects-listing', 'pagination-index-previous-label', 'Voir %items% précédents', NOW(), NOW())
TRANSLATIONS
        );
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('TRUNCATE projects_status');
        $this->addSql(
            <<<'INSERT'
INSERT INTO projects_status (id_project_status, label, status)
VALUES
  (1, 'À compléter', 10),
  (2, 'Revue', 20),
  (3, 'En cours de financement', 30),
  (4, 'En attente de signature', 40),
  (5, 'Signé', 50),
  (6, 'En cours de remboursement', 60),
  (7, 'Remboursé', 70),
  (8, 'Perte', 80),
  (9, 'Annulé', 100)
INSERT
);
        $this->addSql('UPDATE projects SET status = 80 WHERE status = 70');
        $this->addSql('UPDATE projects SET status = 70 WHERE status = 60');
        $this->addSql('UPDATE projects SET status = 60 WHERE status = 50');
        $this->addSql('UPDATE projects SET status = 40 WHERE status = 30');
        $this->addSql('UPDATE projects SET status = 30 WHERE status = 20');
        $this->addSql('UPDATE projects_status_history SET id_project_status = 8 WHERE id_project_status = 7');
        $this->addSql('UPDATE projects_status_history SET id_project_status = 7 WHERE id_project_status = 6');
        $this->addSql('UPDATE projects_status_history SET id_project_status = 6 WHERE id_project_status = 5');
        $this->addSql('UPDATE projects_status_history SET id_project_status = 4 WHERE id_project_status = 3');
        $this->addSql('UPDATE projects_status_history SET id_project_status = 3 WHERE id_project_status = 2');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');

        $this->addSql('DELETE FROM translations WHERE section = "projects-listing"');
    }
}
