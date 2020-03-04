<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200304134221 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1228 Replace Client by Staff for addedBy attribute';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tranche_offer DROP FOREIGN KEY FK_4E7E9DEC699B6BAF');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC47549699B6BAF');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC47549699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE staff_log DROP FOREIGN KEY FK_133F30C699B6BAF');
        $this->addSql('ALTER TABLE staff_log ADD CONSTRAINT FK_133F30C699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE project_status DROP FOREIGN KEY FK_6CA48E56699B6BAF');
        $this->addSql('ALTER TABLE project_status ADD CONSTRAINT FK_6CA48E56699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE project_organizer DROP FOREIGN KEY FK_88E834A4699B6BAF');
        $this->addSql('ALTER TABLE project_organizer ADD CONSTRAINT FK_88E834A4699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB3699B6BAF');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE project_participation_offer DROP FOREIGN KEY FK_1C090985699B6BAF');
        $this->addSql('ALTER TABLE project_participation_offer ADD CONSTRAINT FK_1C090985699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE project_message DROP FOREIGN KEY FK_20A33C1A699B6BAF');
        $this->addSql('ALTER TABLE project_message ADD CONSTRAINT FK_20A33C1A699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB699B6BAF');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE attachment_download DROP FOREIGN KEY FK_7C093130E173B1B8');
        $this->addSql('DROP INDEX IDX_7C093130E173B1B8 ON attachment_download');
        $this->addSql('ALTER TABLE attachment_download CHANGE id_client added_by INT NOT NULL');
        $this->addSql('ALTER TABLE attachment_download ADD CONSTRAINT FK_7C093130699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('CREATE INDEX IDX_7C093130699B6BAF ON attachment_download (added_by)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB699B6BAF');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE attachment_download DROP FOREIGN KEY FK_7C093130699B6BAF');
        $this->addSql('DROP INDEX IDX_7C093130699B6BAF ON attachment_download');
        $this->addSql('ALTER TABLE attachment_download CHANGE added_by id_client INT NOT NULL');
        $this->addSql('ALTER TABLE attachment_download ADD CONSTRAINT FK_7C093130E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_7C093130E173B1B8 ON attachment_download (id_client)');
        $this->addSql('ALTER TABLE project_message DROP FOREIGN KEY FK_20A33C1A699B6BAF');
        $this->addSql('ALTER TABLE project_message ADD CONSTRAINT FK_20A33C1A699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_organizer DROP FOREIGN KEY FK_88E834A4699B6BAF');
        $this->addSql('ALTER TABLE project_organizer ADD CONSTRAINT FK_88E834A4699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC47549699B6BAF');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC47549699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB3699B6BAF');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation_offer DROP FOREIGN KEY FK_1C090985699B6BAF');
        $this->addSql('ALTER TABLE project_participation_offer ADD CONSTRAINT FK_1C090985699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_status DROP FOREIGN KEY FK_6CA48E56699B6BAF');
        $this->addSql('ALTER TABLE project_status ADD CONSTRAINT FK_6CA48E56699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE staff_log DROP FOREIGN KEY FK_133F30C699B6BAF');
        $this->addSql('ALTER TABLE staff_log ADD CONSTRAINT FK_133F30C699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tranche_offer DROP FOREIGN KEY FK_4E7E9DEC699B6BAF');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
