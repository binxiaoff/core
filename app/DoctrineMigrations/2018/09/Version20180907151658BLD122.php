<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180907151658BLD122 extends AbstractMigration
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
        $this->addSql(<<<'TRANSLATIONS'
INSERT IGNORE INTO translations (locale, section, name, translation, added, updated) VALUES
  ('fr_FR', 'lender-operations', 'loans-table-project-status-label-pending', 'Les détails de votre prêt apparaitront ici dès que votre offre sera définitivement acceptée.', NOW(), NOW()),
  ('fr_FR', 'lender-operations', 'loans-chart-legend-loan-status-pending', '%count% en attente d''acceptation', NOW(), NOW()),
  ('fr_FR', 'lender-operations', 'project-status-label-filter-pending', 'En attente d''acceptation', NOW(), NOW()),
  ('fr_FR', 'lender-operations', 'detailed-loan-status-label-pending', 'En attente d''acceptation', NOW(), NOW()),
  ('fr_FR', 'lender-loans', 'pdf-project-status-label-pending', 'Les détails de votre prêt apparaitront ici dès que votre offre sera définitivement acceptée.', NOW(), NOW())
TRANSLATIONS
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
        $this->addSql('DELETE FROM translations WHERE section = "lender-operations" AND name IN ("loans-table-project-status-label-pending", "loans-chart-legend-loan-status-pending", "project-status-label-filter-pending", "detailed-loan-status-label-pending")');
        $this->addSql('DELETE FROM translations WHERE section = "lender-loans" AND name = "pdf-project-status-label-pending"');
    }
}
