<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191224152249 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'TECH-209 (MOve organizer into their own table)';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE project_organizer (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, id_company INT NOT NULL, added_by INT NOT NULL, roles JSON NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', permission_permission SMALLINT DEFAULT 1 NOT NULL, INDEX IDX_88E834A4F12E799E (id_project), INDEX IDX_88E834A49122A03F (id_company), INDEX IDX_88E834A4699B6BAF (added_by), UNIQUE INDEX UNIQ_88E834A4F12E799E9122A03F (id_project, id_company), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_organizer ADD CONSTRAINT FK_88E834A4F12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_organizer ADD CONSTRAINT FK_88E834A49122A03F FOREIGN KEY (id_company) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE project_organizer ADD CONSTRAINT FK_88E834A4699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client)');
        $this->addSql('INSERT INTO project_organizer SELECT NULL, id_project, id_company, added_by, roles, updated, added, permission FROM project_participation WHERE roles NOT LIKE \'%participant%\'');

        foreach ($this->getRoles() as $current => $replacement) {
            $this->addSql("UPDATE project_organizer set roles = REPLACE(roles, '{$current}', '{$replacement}')");
        }

        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB3AE73E249');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3AE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_participation_fee DROP FOREIGN KEY FK_28BEA4AE73E249');
        $this->addSql('ALTER TABLE project_participation_fee ADD CONSTRAINT FK_28BEA4AE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id) ON DELETE CASCADE');
        $this->addSql('DELETE FROM project_participation WHERE roles NOT LIKE \'%participant%\'');
        $this->addSql('ALTER TABLE project_participation DROP roles, DROP permission');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE project_organizer');
        $this->addSql('ALTER TABLE project_participation ADD roles JSON NOT NULL, ADD permission SMALLINT DEFAULT 1 NOT NULL');
    }

    private function getRoles()
    {
        return [
            'Co-arrangeur'      => 'deputy_arranger',
            'Arrangeur'         => 'arranger',
            'Agent des sûretés' => 'security_trustee',
            'Agent du crédit'   => 'loan_officer',
            'RUN'               => 'run',
        ];
    }
}
