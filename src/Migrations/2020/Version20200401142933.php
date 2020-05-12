<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200401142933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Bring up such changes like file encryption and PSN from develop. Delete project image field';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE file_version_signature (id INT AUTO_INCREMENT NOT NULL, id_file_version INT NOT NULL, id_signatory INT NOT NULL, added_by INT NOT NULL, status SMALLINT NOT NULL, transaction_number VARCHAR(100) DEFAULT NULL, signature_url VARCHAR(255) DEFAULT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_E3BD6857B5B48B91 (public_id), INDEX IDX_E3BD6857C7BB1F8A (id_file_version), INDEX IDX_E3BD68572B0DC78F (id_signatory), INDEX IDX_E3BD6857699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE file_version_signature ADD CONSTRAINT FK_E3BD6857C7BB1F8A FOREIGN KEY (id_file_version) REFERENCES file_version (id)');
        $this->addSql('ALTER TABLE file_version_signature ADD CONSTRAINT FK_E3BD68572B0DC78F FOREIGN KEY (id_signatory) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE file_version_signature ADD CONSTRAINT FK_E3BD6857699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('DROP TABLE attachment_signature');
        $this->addSql('ALTER TABLE file_version ADD encryption_key VARCHAR(512) DEFAULT NULL, ADD mime_type VARCHAR(150) DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_2FB3D0EEC53D045F ON project');
        $this->addSql('ALTER TABLE project DROP image');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE attachment_signature (id INT AUTO_INCREMENT NOT NULL, id_attachment INT NOT NULL, id_signatory INT NOT NULL, added_by INT NOT NULL, status SMALLINT NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', public_id VARCHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, transaction_number VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, signature_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_D85053622B0DC78F (id_signatory), INDEX IDX_D8505362699B6BAF (added_by), INDEX IDX_D8505362DCD5596C (id_attachment), UNIQUE INDEX UNIQ_D8505362B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE attachment_signature ADD CONSTRAINT FK_D85053622B0DC78F FOREIGN KEY (id_signatory) REFERENCES staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE attachment_signature ADD CONSTRAINT FK_D8505362699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE attachment_signature ADD CONSTRAINT FK_D8505362DCD5596C FOREIGN KEY (id_attachment) REFERENCES file_version (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE file_version_signature');
        $this->addSql('ALTER TABLE file_version DROP encryption_key, DROP mime_type');
        $this->addSql('ALTER TABLE project ADD image VARCHAR(320) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FB3D0EEC53D045F ON project (image)');
    }
}
