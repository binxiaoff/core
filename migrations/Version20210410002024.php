<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210410002024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update status handling for agency project';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BF41AF0274');
        $this->addSql('CREATE TABLE agency_project_status_history (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, added_by INT NOT NULL, status INT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_715F046CF12E799E (id_project), INDEX IDX_715F046C699B6BAF (added_by), UNIQUE INDEX UNIQ_715F046CF12E799E7B00651C (id_project, status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO agency_project_status_history SELECT NULL, ag.id, ag.added_by, 10, NOW() FROM agency_project ag');
        $this->addSql('INSERT INTO agency_project_status_history SELECT NULL, ag.id, ag.added_by, 20, NOW() FROM agency_project ag INNER JOIN agency_project_status ags WHERE ags.status = 20');
        $this->addSql('ALTER TABLE agency_project_status_history ADD CONSTRAINT FK_715F046CF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_project_status_history ADD CONSTRAINT FK_715F046C699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id)');
        $this->addSql('DROP INDEX UNIQ_59B349BF41AF0274 ON agency_project');
        $this->addSql('ALTER TABLE agency_project ADD current_status SMALLINT NOT NULL');
        $this->addSql('UPDATE agency_project SET current_status = 10 WHERE TRUE');
        $this->addSql('UPDATE agency_project ag INNER JOIN agency_project_status aps ON ag.id_current_status = aps.id AND ag.id = aps.id_project SET ag.current_status = 20 WHERE aps.status = 20');
        $this->addSql('ALTER TABLE agency_project DROP id_current_status');
        $this->addSql('DROP TABLE agency_project_status');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE agency_project_status (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, added_by INT NOT NULL, status INT NOT NULL, INDEX IDX_9D3BD49E699B6BAF (added_by), INDEX IDX_9D3BD49EF12E799E (id_project), UNIQUE INDEX UNIQ_9D3BD49EF12E799E7B00651C (id_project, status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE agency_project_status ADD CONSTRAINT FK_9D3BD49E699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_project_status ADD CONSTRAINT FK_9D3BD49EF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('INSERT INTO agency_project_status SELECT NULL, id, added_by, 10 FROM agency_project');
        $this->addSql('INSERT INTO agency_project_status SELECT NULL, id, added_by, 20 FROM agency_project WHERE current_status = 20');
        $this->addSql('DROP TABLE agency_project_status_history');
        $this->addSql('ALTER TABLE agency_project ADD id_current_status INT DEFAULT NULL');
        $this->addSql('UPDATE agency_project ap INNER JOIN agency_project_status aps ON ap.id_current_status = ap.current_status AND ap.id = aps.id_project SET id_current_status = aps.id WHERE TRUE');
        $this->addSql('ALTER TABLE agency_project DROP current_status');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BF41AF0274 FOREIGN KEY (id_current_status) REFERENCES agency_project_status (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_59B349BF41AF0274 ON agency_project (id_current_status)');
    }
}
