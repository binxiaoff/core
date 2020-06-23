<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200622092934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1492 Replace client reference into staff reference in ProjectParticipationContact';
    }

    public function up(Schema $schema): void
    {
        $incorrectProjectParticipationContacts = $this->connection->executeQuery('
        SELECT ppc.id
FROM project_participation_contact ppc
INNER JOIN project_participation pp on ppc.id_project_participation = pp.id
INNER JOIN company c on pp.id_company = c.id
WHERE NOT EXISTS(
    SELECT * FROM staff WHERE id_company = pp.id_company AND id_client = ppc.id_client
);')->fetchAll();

        if ($incorrectProjectParticipationContacts) {
            $incorrectProjectParticipationContacts = array_column($incorrectProjectParticipationContacts, 'id');
            $incorrectProjectParticipationContacts = implode(', ', $incorrectProjectParticipationContacts);
            $this->warnIf(true, "The participationContacts with ids {$incorrectProjectParticipationContacts} will be deleted");
            $this->addSql("DELETE FROM project_participation_contact WHERE id IN ({$incorrectProjectParticipationContacts})");
        }

        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB3E173B1B8');
        $this->addSql('DROP INDEX IDX_41530AB3E173B1B8 ON project_participation_contact');
        $this->addSql('DROP INDEX UNIQ_41530AB3E173B1B8AE73E249 ON project_participation_contact');
        $this->addSql('ALTER TABLE project_participation_contact ADD COLUMN id_staff INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_41530AB3ACEBB2A2 ON project_participation_contact (id_staff)');
        $this->addSql('
            UPDATE project_participation_contact ppc INNER JOIN project_participation pp on ppc.id_project_participation = pp.id
            SET id_staff = (SELECT id FROM staff s WHERE ppc.id_client = s.id_client AND s.id_company = pp.id_company)
            WHERE 1 = 1
        ');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3ACEBB2A2 FOREIGN KEY (id_staff) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE project_participation_contact DROP COLUMN id_client');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_41530AB3ACEBB2A2AE73E249 ON project_participation_contact (id_staff, id_project_participation)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB3ACEBB2A2');
        $this->addSql('DROP INDEX IDX_41530AB3ACEBB2A2 ON project_participation_contact');
        $this->addSql('DROP INDEX UNIQ_41530AB3ACEBB2A2AE73E249 ON project_participation_contact');
        $this->addSql('ALTER TABLE project_participation_contact ADD COLUMN id_client INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_41530AB3E173B1B8 ON project_participation_contact (id_client)');
        $this->addSql('UPDATE project_participation_contact SET id_client = (SELECT id_client FROM staff WHERE project_participation_contact.id_staff = staff.id)');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation_contact DROP COLUMN id_staff');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_41530AB3E173B1B8AE73E249 ON project_participation_contact (id_client, id_project_participation)');
    }
}
