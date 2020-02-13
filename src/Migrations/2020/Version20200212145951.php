<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200212145951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-980 Rename table clients: "id_client" to "id" & "hash" to "public_id".';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC47549699B6BAF');
        $this->addSql('ALTER TABLE staff_log DROP FOREIGN KEY FK_133F30C699B6BAF');
        $this->addSql('ALTER TABLE project_status DROP FOREIGN KEY FK_C6DD336CE7CA843C');
        $this->addSql('ALTER TABLE project_organizer DROP FOREIGN KEY FK_88E834A4699B6BAF');
        $this->addSql('ALTER TABLE temporary_token DROP FOREIGN KEY FK_A6F42CE8E173B1B8');
        $this->addSql('ALTER TABLE project_comment DROP FOREIGN KEY FK_26A5E09E173B1B8');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEEE78DD55');
        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB3699B6BAF');
        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB3E173B1B8');
        $this->addSql('ALTER TABLE attachment_signature DROP FOREIGN KEY FK_D85053622B0DC78F');
        $this->addSql('ALTER TABLE project_participation_offer DROP FOREIGN KEY FK_1C09098516FE72E1');
        $this->addSql('ALTER TABLE project_participation_offer DROP FOREIGN KEY FK_1C090985699B6BAF');
        $this->addSql('ALTER TABLE client_status DROP FOREIGN KEY FK_3E28AF07AB014612');
        $this->addSql('ALTER TABLE project_message DROP FOREIGN KEY FK_20A33C1A699B6BAF');
        $this->addSql('ALTER TABLE client_successful_login DROP FOREIGN KEY FK_19D1D044E173B1B8');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB141E829E');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BBE7CA843C');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BBE8DE7170');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAE173B1B8');
        $this->addSql('ALTER TABLE acceptations_legal_docs DROP FOREIGN KEY FK_F1D2E432E173B1B8');
        $this->addSql('ALTER TABLE user_agent DROP FOREIGN KEY FK_1B67BFB1E173B1B8');
        $this->addSql('ALTER TABLE staff DROP FOREIGN KEY FK_426EF392E173B1B8');
        $this->addSql('ALTER TABLE attachment_download DROP FOREIGN KEY FK_7C093130E173B1B8');
        $this->addSql('ALTER TABLE tranche_offer DROP FOREIGN KEY FK_4E7E9DEC16FE72E1');
        $this->addSql('ALTER TABLE tranche_offer DROP FOREIGN KEY FK_4E7E9DEC699B6BAF');

        $this->addSql('ALTER TABLE clients MODIFY id_client INT NOT NULL ');
        $this->addSql('ALTER TABLE clients DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE clients CHANGE id_client id INT NOT NULL');
        $this->addSql('ALTER TABLE clients ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE clients CHANGE id id INT AUTO_INCREMENT NOT NULL');

        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC47549699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE staff_log ADD CONSTRAINT FK_133F30C699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE project_status ADD CONSTRAINT FK_6CA48E56699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE project_organizer ADD CONSTRAINT FK_88E834A4699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE temporary_token ADD CONSTRAINT FK_B7D82B15E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE project_comment ADD CONSTRAINT FK_26A5E09E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEEE78DD55 FOREIGN KEY (id_client_submitter) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE attachment_signature ADD CONSTRAINT FK_D85053622B0DC78F FOREIGN KEY (id_signatory) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE project_participation_offer ADD CONSTRAINT FK_1C09098516FE72E1 FOREIGN KEY (updated_by) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE project_participation_offer ADD CONSTRAINT FK_1C090985699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE client_status ADD CONSTRAINT FK_2EA62EAFE173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE project_message ADD CONSTRAINT FK_20A33C1A699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE client_successful_login ADD CONSTRAINT FK_94E06715E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB51B07D6D FOREIGN KEY (archived_by) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB16FE72E1 FOREIGN KEY (updated_by) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE acceptations_legal_docs ADD CONSTRAINT FK_F1D2E432E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE user_agent ADD CONSTRAINT FK_C44967C5E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE staff ADD CONSTRAINT FK_426EF392E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE attachment_download ADD CONSTRAINT FK_7C093130E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC16FE72E1 FOREIGN KEY (updated_by) REFERENCES clients (id)');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id)');

        $this->addSql('ALTER TABLE clients CHANGE hash public_id VARCHAR(191) NOT NULL');
        $this->addSql('CREATE INDEX IDX_C82E74B5B48B91 ON clients (public_id)');

        $this->addSql('ALTER TABLE clients CHANGE public_id public_id VARCHAR(36) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C82E74B5B48B91 ON clients (public_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acceptations_legal_docs DROP FOREIGN KEY FK_F1D2E432E173B1B8');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB699B6BAF');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB51B07D6D');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB16FE72E1');
        $this->addSql('ALTER TABLE attachment_download DROP FOREIGN KEY FK_7C093130E173B1B8');
        $this->addSql('ALTER TABLE attachment_signature DROP FOREIGN KEY FK_D85053622B0DC78F');
        $this->addSql('ALTER TABLE client_status DROP FOREIGN KEY FK_2EA62EAFE173B1B8');
        $this->addSql('ALTER TABLE client_successful_login DROP FOREIGN KEY FK_94E06715E173B1B8');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAE173B1B8');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEEE78DD55');
        $this->addSql('ALTER TABLE project_comment DROP FOREIGN KEY FK_26A5E09E173B1B8');
        $this->addSql('ALTER TABLE project_message DROP FOREIGN KEY FK_20A33C1A699B6BAF');
        $this->addSql('ALTER TABLE project_organizer DROP FOREIGN KEY FK_88E834A4699B6BAF');
        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC47549699B6BAF');
        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB3E173B1B8');
        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB3699B6BAF');
        $this->addSql('ALTER TABLE project_participation_offer DROP FOREIGN KEY FK_1C090985699B6BAF');
        $this->addSql('ALTER TABLE project_participation_offer DROP FOREIGN KEY FK_1C09098516FE72E1');
        $this->addSql('ALTER TABLE project_status DROP FOREIGN KEY FK_6CA48E56699B6BAF');
        $this->addSql('ALTER TABLE staff DROP FOREIGN KEY FK_426EF392E173B1B8');
        $this->addSql('ALTER TABLE staff_log DROP FOREIGN KEY FK_133F30C699B6BAF');
        $this->addSql('ALTER TABLE temporary_token DROP FOREIGN KEY FK_B7D82B15E173B1B8');
        $this->addSql('ALTER TABLE tranche_offer DROP FOREIGN KEY FK_4E7E9DEC699B6BAF');
        $this->addSql('ALTER TABLE tranche_offer DROP FOREIGN KEY FK_4E7E9DEC16FE72E1');
        $this->addSql('ALTER TABLE user_agent DROP FOREIGN KEY FK_C44967C5E173B1B8');

        $this->addSql('ALTER TABLE clients MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE clients DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE clients CHANGE id id_client INT NOT NULL');
        $this->addSql('ALTER TABLE clients ADD PRIMARY KEY (id_client)');
        $this->addSql('ALTER TABLE clients CHANGE id_client id_client INT AUTO_INCREMENT NOT NULL');

        $this->addSql('ALTER TABLE acceptations_legal_docs ADD CONSTRAINT FK_F1D2E432E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB141E829E FOREIGN KEY (archived_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBE7CA843C FOREIGN KEY (added_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBE8DE7170 FOREIGN KEY (updated_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE attachment_download ADD CONSTRAINT FK_7C093130E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE attachment_signature ADD CONSTRAINT FK_D85053622B0DC78F FOREIGN KEY (id_signatory) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE client_status ADD CONSTRAINT FK_3E28AF07AB014612 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE client_successful_login ADD CONSTRAINT FK_19D1D044E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEEE78DD55 FOREIGN KEY (id_client_submitter) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_comment ADD CONSTRAINT FK_26A5E09E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_message ADD CONSTRAINT FK_20A33C1A699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_organizer ADD CONSTRAINT FK_88E834A4699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC47549699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation_offer ADD CONSTRAINT FK_1C090985699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation_offer ADD CONSTRAINT FK_1C09098516FE72E1 FOREIGN KEY (updated_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_status ADD CONSTRAINT FK_C6DD336CE7CA843C FOREIGN KEY (added_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE staff ADD CONSTRAINT FK_426EF392E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE staff_log ADD CONSTRAINT FK_133F30C699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE temporary_token ADD CONSTRAINT FK_A6F42CE8E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC16FE72E1 FOREIGN KEY (updated_by) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE user_agent ADD CONSTRAINT FK_1B67BFB1E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');

        $this->addSql('DROP INDEX IDX_C82E74B5B48B91 ON clients');
        $this->addSql('ALTER TABLE clients CHANGE public_id hash VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci');

        $this->addSql('DROP INDEX UNIQ_C82E74B5B48B91 ON clients');
        $this->addSql('ALTER TABLE clients CHANGE hash hash VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci');
    }
}
