<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210226170456 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '[Agency] Remove AbstractContainerFile';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE abstract_file_container_file DROP FOREIGN KEY FK_EB7D8B89FD6A21F6');
        $this->addSql('ALTER TABLE core_drive DROP FOREIGN KEY FK_3E9CD46FBF396750');
        $this->addSql('ALTER TABLE core_folder DROP FOREIGN KEY FK_4CFE6A94BF396750');
        $this->addSql('ALTER TABLE core_folder DROP FOREIGN KEY FK_4CFE6A948698B4BF');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFAD3D811');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFB5F15EDF');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFA678F821');
        $this->addSql('DROP TABLE abstract_file_container');
        $this->addSql('DROP TABLE abstract_file_container_file');
        $this->addSql('ALTER TABLE core_drive CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE core_folder ADD public_id VARCHAR(36) NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4CFE6A94B5B48B91 ON core_folder (public_id)');
        $this->addSql('ALTER TABLE core_folder ADD CONSTRAINT FK_4CFE6A948698B4BF FOREIGN KEY (id_drive) REFERENCES core_drive (id)');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFA678F821 FOREIGN KEY (id_agent_borrower_drive) references core_drive (id)');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFAD3D811 FOREIGN KEY (id_agent_principal_borrower_drive) references core_drive (id)');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFB5F15EDF FOREIGN KEY (id_agent_secondary_borrower_drive) references core_drive (id)');
        $this->addSql('CREATE TABLE folder_file (folder_id INT NOT NULL, file_id INT NOT NULL, INDEX IDX_95001005162CB942 (folder_id), UNIQUE INDEX UNIQ_9500100593CB796C (file_id), PRIMARY KEY(folder_id, file_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE folder_file ADD CONSTRAINT FK_95001005162CB942 FOREIGN KEY (folder_id) REFERENCES core_folder (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE folder_file ADD CONSTRAINT FK_9500100593CB796C FOREIGN KEY (file_id) REFERENCES core_file (id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE abstract_file_container (id INT AUTO_INCREMENT NOT NULL, discr VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE abstract_file_container_file (abstract_file_container_id INT NOT NULL, file_id INT NOT NULL, INDEX IDX_EB7D8B89FD6A21F6 (abstract_file_container_id), UNIQUE INDEX UNIQ_EB7D8B8993CB796C (file_id), PRIMARY KEY(abstract_file_container_id, file_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE abstract_file_container_file ADD CONSTRAINT FK_EB7D8B8993CB796C FOREIGN KEY (file_id) REFERENCES core_file (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE abstract_file_container_file ADD CONSTRAINT FK_EB7D8B89FD6A21F6 FOREIGN KEY (abstract_file_container_id) REFERENCES abstract_file_container (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('DROP TABLE folder_file');
        $this->addSql('ALTER TABLE core_folder DROP FOREIGN KEY FK_4CFE6A948698B4BF');

        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFAD3D811');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFB5F15EDF');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFA678F821');
        $this->addSql('ALTER TABLE core_drive CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE core_folder ADD CONSTRAINT FK_4CFE6A948698B4BF FOREIGN KEY (id_drive) REFERENCES core_drive (id)');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFA678F821 FOREIGN KEY (id_agent_borrower_drive) references core_drive (id)');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFAD3D811 FOREIGN KEY (id_agent_principal_borrower_drive) references core_drive (id)');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFB5F15EDF FOREIGN KEY (id_agent_secondary_borrower_drive) references core_drive (id)');
        $this->addSql('ALTER TABLE core_drive ADD CONSTRAINT FK_3E9CD46FBF396750 FOREIGN KEY (id) REFERENCES abstract_file_container (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('DROP INDEX UNIQ_4CFE6A94B5B48B91 ON core_folder');
        $this->addSql('ALTER TABLE core_folder DROP public_id, CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE core_folder ADD CONSTRAINT FK_4CFE6A94BF396750 FOREIGN KEY (id) REFERENCES abstract_file_container (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
