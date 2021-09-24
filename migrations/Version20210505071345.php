<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 *
 * TODO Finalize after rebase
 */
final class Version20210505071345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agency_participation DROP FOREIGN KEY FK_E0ED689EF0C7A460');
        $this->addSql('ALTER TABLE agency_participation ADD CONSTRAINT FK_E0ED689EF0C7A460 FOREIGN KEY (id_participation_pool) REFERENCES agency_participation_pool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_participation RENAME INDEX uniq_e0ed689ed220e9f TO UNIQ_E0ED689E7754ACA8');
        $this->addSql('ALTER TABLE agency_participation_pool DROP FOREIGN KEY FK_9D542F1FF12E799E');
        $this->addSql('ALTER TABLE agency_participation_pool ADD shared_drive_id INT NOT NULL');
        $this->addSql('ALTER TABLE agency_participation_pool ADD CONSTRAINT FK_9D542F1FD3C111E7 FOREIGN KEY (shared_drive_id) REFERENCES core_drive (id)');
        $this->addSql('ALTER TABLE agency_participation_pool ADD CONSTRAINT FK_9D542F1FF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9D542F1FD3C111E7 ON agency_participation_pool (shared_drive_id)');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFA678F821');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFAD3D811');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFB5F15EDF');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFC2E752FB');
        $this->addSql('DROP INDEX UNIQ_59B349BFA678F821 ON agency_project');
        $this->addSql('DROP INDEX UNIQ_59B349BFAD3D811 ON agency_project');
        $this->addSql('DROP INDEX UNIQ_59B349BFB5F15EDF ON agency_project');
        $this->addSql('DROP INDEX UNIQ_59B349BFC2E752FB ON agency_project');
        $this->addSql('ALTER TABLE agency_project ADD id_borrower_shared_drive INT NOT NULL, ADD id_borrower_confidential_drive INT NOT NULL, DROP id_agent_borrower_drive, DROP id_agent_principal_borrower_drive, DROP id_agent_secondary_borrower_drive, DROP id_borrower_drive');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFAA1F7C61 FOREIGN KEY (id_borrower_shared_drive) REFERENCES core_drive (id)');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BF5743F072 FOREIGN KEY (id_borrower_confidential_drive) REFERENCES core_drive (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_59B349BFAA1F7C61 ON agency_project (id_borrower_shared_drive)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_59B349BF5743F072 ON agency_project (id_borrower_confidential_drive)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agency_participation DROP FOREIGN KEY FK_E0ED689EF0C7A460');
        $this->addSql('ALTER TABLE agency_participation ADD CONSTRAINT FK_E0ED689EF0C7A460 FOREIGN KEY (id_participation_pool) REFERENCES agency_participation_pool (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_participation RENAME INDEX uniq_e0ed689e7754aca8 TO UNIQ_E0ED689ED220E9F');
        $this->addSql('ALTER TABLE agency_participation_pool DROP FOREIGN KEY FK_9D542F1FD3C111E7');
        $this->addSql('ALTER TABLE agency_participation_pool DROP FOREIGN KEY FK_9D542F1FF12E799E');
        $this->addSql('DROP INDEX UNIQ_9D542F1FD3C111E7 ON agency_participation_pool');
        $this->addSql('ALTER TABLE agency_participation_pool DROP shared_drive_id');
        $this->addSql('ALTER TABLE agency_participation_pool ADD CONSTRAINT FK_9D542F1FF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFAA1F7C61');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BF5743F072');
        $this->addSql('DROP INDEX UNIQ_59B349BFAA1F7C61 ON agency_project');
        $this->addSql('DROP INDEX UNIQ_59B349BF5743F072 ON agency_project');
        $this->addSql('ALTER TABLE agency_project ADD id_agent_borrower_drive INT NOT NULL, ADD id_agent_principal_borrower_drive INT NOT NULL, ADD id_agent_secondary_borrower_drive INT NOT NULL, ADD id_borrower_drive INT NOT NULL, DROP id_borrower_shared_drive, DROP id_borrower_confidential_drive');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFA678F821 FOREIGN KEY (id_agent_borrower_drive) REFERENCES core_drive (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFAD3D811 FOREIGN KEY (id_agent_principal_borrower_drive) REFERENCES core_drive (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFB5F15EDF FOREIGN KEY (id_agent_secondary_borrower_drive) REFERENCES core_drive (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFC2E752FB FOREIGN KEY (id_borrower_drive) REFERENCES core_drive (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_59B349BFA678F821 ON agency_project (id_agent_borrower_drive)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_59B349BFAD3D811 ON agency_project (id_agent_principal_borrower_drive)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_59B349BFB5F15EDF ON agency_project (id_agent_secondary_borrower_drive)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_59B349BFC2E752FB ON agency_project (id_borrower_drive)');
    }
}
