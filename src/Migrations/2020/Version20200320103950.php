<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200320103950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1311 Migrate datas from Attachment to ProjectFile, File and FileVersion';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE project_file (id INT AUTO_INCREMENT NOT NULL, id_file INT NOT NULL, id_project INT NOT NULL, added_by INT NOT NULL, type VARCHAR(60) NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_B50EFE087BF2A12 (id_file), INDEX IDX_B50EFE08F12E799E (id_project), INDEX IDX_B50EFE08699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE file (id INT AUTO_INCREMENT NOT NULL, id_current_file_version INT DEFAULT NULL, archived_by INT DEFAULT NULL, description VARCHAR(191) DEFAULT NULL, archived DATETIME DEFAULT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8C9F3610B5B48B91 (public_id), UNIQUE INDEX UNIQ_8C9F3610FC4F95CE (id_current_file_version), INDEX IDX_8C9F361051B07D6D (archived_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE file_version (id INT AUTO_INCREMENT NOT NULL, file_id INT NOT NULL, added_by INT NOT NULL, path VARCHAR(191) NOT NULL, original_name VARCHAR(191) DEFAULT NULL, size INT DEFAULT NULL, file_system VARCHAR(255) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_E47A6AF8B5B48B91 (public_id), INDEX IDX_E47A6AF893CB796C (file_id), INDEX IDX_E47A6AF8699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE file_download (id INT AUTO_INCREMENT NOT NULL, id_file_version INT NOT NULL, added_by INT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C94A0DEDC7BB1F8A (id_file_version), INDEX IDX_C94A0DED699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE project_file ADD CONSTRAINT FK_B50EFE087BF2A12 FOREIGN KEY (id_file) REFERENCES file (id)');
        $this->addSql('ALTER TABLE project_file ADD CONSTRAINT FK_B50EFE08F12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_file ADD CONSTRAINT FK_B50EFE08699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F3610FC4F95CE FOREIGN KEY (id_current_file_version) REFERENCES file_version (id)');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F361051B07D6D FOREIGN KEY (archived_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE file_version ADD CONSTRAINT FK_E47A6AF893CB796C FOREIGN KEY (file_id) REFERENCES file (id)');
        $this->addSql('ALTER TABLE file_version ADD CONSTRAINT FK_E47A6AF8699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE file_download ADD CONSTRAINT FK_C94A0DEDC7BB1F8A FOREIGN KEY (id_file_version) REFERENCES file_version (id)');
        $this->addSql('ALTER TABLE file_download ADD CONSTRAINT FK_C94A0DED699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE project ADD id_description_document INT DEFAULT NULL, ADD id_confidentiality_disclaimer INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE61AA99F6 FOREIGN KEY (id_description_document) REFERENCES file (id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE3018FCE3 FOREIGN KEY (id_confidentiality_disclaimer) REFERENCES file (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FB3D0EE61AA99F6 ON project (id_description_document)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FB3D0EE3018FCE3 ON project (id_confidentiality_disclaimer)');
        $this->addSql('ALTER TABLE project_participation_contact ADD id_accepted_confidentiality_disclaimer_version INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3D8274047 FOREIGN KEY (id_accepted_confidentiality_disclaimer_version) REFERENCES file_version (id)');
        $this->addSql('CREATE INDEX IDX_41530AB3D8274047 ON project_participation_contact (id_accepted_confidentiality_disclaimer_version)');
        $this->addSql('ALTER TABLE attachment_signature ADD CONSTRAINT FK_D8505362DCD5596C FOREIGN KEY (id_attachment) REFERENCES file_version (id)');

        $this->addSql('INSERT INTO file (id, description, public_id, updated, added, archived, archived_by)
            SELECT id, description, public_id, updated, added, archived, archived_by
            FROM attachment
            ');

        $this->addSql('INSERT INTO file_version (id, file_id, added_by, path, original_name, size, file_system, updated, added, public_id)
            SELECT id, id, added_by, path, original_name, size, "user_attachment", updated, added, public_id
            FROM attachment
        ');

        $this->addSql('UPDATE file SET id_current_file_version = id');

        $this->addSql('INSERT INTO file_download (id_file_version, added_by, added)
        SELECT ad.id_attachment, ad.added_by, ad.added
        FROM attachment_download ad ');

        $this->addSql('INSERT INTO project_file (id_file, id_project, added_by, type, added)
            SELECT id, id_project, added_by, type, added
            FROM attachment
        ');

        $this->addSql('DROP TABLE attachment');
        $this->addSql('DROP TABLE attachment_download');
        $this->addSql('DROP TABLE zz_versioned_attachment');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_file DROP FOREIGN KEY FK_B50EFE087BF2A12');
        $this->addSql('ALTER TABLE file_version DROP FOREIGN KEY FK_E47A6AF893CB796C');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE61AA99F6');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE3018FCE3');
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F3610FC4F95CE');
        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB3D8274047');
        $this->addSql('ALTER TABLE attachment_signature DROP FOREIGN KEY FK_D8505362DCD5596C');
        $this->addSql('ALTER TABLE file_download DROP FOREIGN KEY FK_C94A0DEDC7BB1F8A');
        $this->addSql('CREATE TABLE attachment (id INT AUTO_INCREMENT NOT NULL, added_by INT NOT NULL, id_project INT NOT NULL, archived_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, public_id VARCHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, path VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, original_name VARCHAR(191) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', archived DATETIME DEFAULT NULL, description VARCHAR(191) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, type VARCHAR(60) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, size INT DEFAULT NULL, INDEX IDX_795FD9BB16FE72E1 (updated_by), INDEX IDX_795FD9BB51B07D6D (archived_by), INDEX IDX_795FD9BB699B6BAF (added_by), INDEX IDX_795FD9BBF12E799E (id_project), UNIQUE INDEX UNIQ_795FD9BBB5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE attachment_download (id INT AUTO_INCREMENT NOT NULL, added_by INT NOT NULL, id_attachment INT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7C093130699B6BAF (added_by), INDEX IDX_7C093130DCD5596C (id_attachment), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE zz_versioned_attachment (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, logged_at DATETIME NOT NULL, object_id VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, version INT NOT NULL, data LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_F532155232D562B69684D7DBF1CD3C3 (object_id, object_class, version), INDEX IDX_F532155A78D87A7 (logged_at), INDEX IDX_F532155F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB16FE72E1 FOREIGN KEY (updated_by) REFERENCES staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB51B07D6D FOREIGN KEY (archived_by) REFERENCES staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBF12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE attachment_download ADD CONSTRAINT FK_7C093130699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('INSERT INTO attachment (id, public_id, added_by, path, original_name, added, archived, id_project, description, archived_by, type, size)
            SELECT f.id, f.public_id, fv.added_by, fv.path, fv.original_name, fv.added, f.archived, f.updated, pf.id_project, f.description, f.archived_by, pf.file, fv.size
            FROM file f
            INNER JOIN file_version fv ON f.id = fv.id
            INNER JOIN project_file pf on f.id = pf.id_file
        ');
        $this->addSql('INSERT INTO attachment_download (id, id_attachment, added_by, added)
        SELECT id, id_file_version, added_by, added
        FROM file_download fd ');
        $this->addSql('DROP TABLE project_file');
        $this->addSql('DROP TABLE file');
        $this->addSql('DROP TABLE file_version');
        $this->addSql('DROP TABLE file_download');
        $this->addSql('DROP INDEX UNIQ_2FB3D0EE61AA99F6 ON project');
        $this->addSql('DROP INDEX UNIQ_2FB3D0EE3018FCE3 ON project');
        $this->addSql('ALTER TABLE project DROP id_description_document, DROP id_confidentiality_disclaimer');
        $this->addSql('DROP INDEX IDX_41530AB3D8274047 ON project_participation_contact');
        $this->addSql('ALTER TABLE project_participation_contact DROP id_accepted_confidentiality_disclaimer_version');
    }
}
