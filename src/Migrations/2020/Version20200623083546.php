<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200623083546 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1361 Rename confidentialityDisclaimer to nda';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE project_file SET type = 'project_file_nda' WHERE type = 'project_file_confidentiality'");

        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE3018FCE3');
        $this->addSql('DROP INDEX UNIQ_2FB3D0EE3018FCE3 ON project');
        $this->addSql('ALTER TABLE project CHANGE id_confidentiality_disclaimer id_nda INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE1888280F FOREIGN KEY (id_nda) REFERENCES file (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FB3D0EE1888280F ON project (id_nda)');
        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB3D8274047');
        $this->addSql('DROP INDEX IDX_41530AB3D8274047 ON project_participation_contact');
        $this->addSql('ALTER TABLE project_participation_contact CHANGE id_accepted_confidentiality_disclaimer_version id_accepted_nda_version INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3EFC7EA74 FOREIGN KEY (id_accepted_nda_version) REFERENCES file_version (id)');
        $this->addSql('CREATE INDEX IDX_41530AB3EFC7EA74 ON project_participation_contact (id_accepted_nda_version)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE project_file SET type = 'project_file_confidentiality' WHERE type = 'project_file_nda'");

        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE1888280F');
        $this->addSql('DROP INDEX UNIQ_2FB3D0EE1888280F ON project');
        $this->addSql('ALTER TABLE project CHANGE id_nda id_confidentiality_disclaimer INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE3018FCE3 FOREIGN KEY (id_confidentiality_disclaimer) REFERENCES file (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FB3D0EE3018FCE3 ON project (id_confidentiality_disclaimer)');
        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB3EFC7EA74');
        $this->addSql('DROP INDEX IDX_41530AB3EFC7EA74 ON project_participation_contact');
        $this->addSql('ALTER TABLE project_participation_contact CHANGE id_accepted_nda_version id_accepted_confidentiality_disclaimer_version INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3D8274047 FOREIGN KEY (id_accepted_confidentiality_disclaimer_version) REFERENCES file_version (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_41530AB3D8274047 ON project_participation_contact (id_accepted_confidentiality_disclaimer_version)');
    }
}
