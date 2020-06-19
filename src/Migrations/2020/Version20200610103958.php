<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;

final class Version20200610103958 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1535 Add public id for project_participation_tranche and project_participation_status';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_participation_status ADD public_id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE project_participation_tranche ADD public_id VARCHAR(36) NOT NULL');

        $statuses = $this->connection->fetchAll('SELECT id FROM project_participation_status');
        $statuses = array_column($statuses, 'id');
        foreach ($statuses as $statusId) {
            $uuid = (Uuid::uuid4())->toString();
            $this->addSql("UPDATE project_participation_status SET public_id = '{$uuid}' WHERE id = {$statusId}");
        }
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2786D096B5B48B91 ON project_participation_status (public_id)');

        $participationTranches = $this->connection->fetchAll('SELECT id FROM project_participation_tranche');
        $participationTranches = array_column($participationTranches, 'id');
        foreach ($participationTranches as $participationTrancheId) {
            $uuid = (Uuid::uuid4())->toString();
            $this->addSql("UPDATE project_participation_tranche SET public_id = '{$uuid}' WHERE id = {$participationTrancheId}");
        }
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6B56B4CBB5B48B91 ON project_participation_tranche (public_id)');

        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES (\'fr_FR\', \'project-participation-status\', \'duplicated-new-status\', \'Vous ne pouvez pas ajouter un nouveau statut qui est en doublons avec le dernier.\', NOW())');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_2786D096B5B48B91 ON project_participation_status');
        $this->addSql('ALTER TABLE project_participation_status DROP public_id');
        $this->addSql('DROP INDEX UNIQ_6B56B4CBB5B48B91 ON project_participation_tranche');
        $this->addSql('ALTER TABLE project_participation_tranche DROP public_id');
    }
}
