<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190503115308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX id_client ON attachment');
        $this->addSql('ALTER TABLE attachment ADD id_owner INT DEFAULT NULL, ADD id_company_owner INT DEFAULT NULL, ADD description VARCHAR(191) DEFAULT NULL, ADD archivedBy INT DEFAULT NULL, ADD updatedBy INT DEFAULT NULL, CHANGE original_name original_name VARCHAR(191) NOT NULL, CHANGE archived archived DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE id_client addedBy INT NOT NULL');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB7E3C61F9 FOREIGN KEY (id_owner) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB66AB7494 FOREIGN KEY (id_company_owner) REFERENCES companies (id_company)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBE7CA843C FOREIGN KEY (addedBy) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB141E829E FOREIGN KEY (archivedBy) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBE8DE7170 FOREIGN KEY (updatedBy) REFERENCES clients (id_client)');
        $this->addSql('CREATE INDEX IDX_795FD9BB7E3C61F9 ON attachment (id_owner)');
        $this->addSql('CREATE INDEX IDX_795FD9BB66AB7494 ON attachment (id_company_owner)');
        $this->addSql('CREATE INDEX IDX_795FD9BBE7CA843C ON attachment (addedBy)');
        $this->addSql('CREATE INDEX IDX_795FD9BB141E829E ON attachment (archivedBy)');
        $this->addSql('CREATE INDEX IDX_795FD9BBE8DE7170 ON attachment (updatedBy)');
        $this->addSql('ALTER TABLE attachment RENAME INDEX fk_attachment_id_type TO IDX_795FD9BB7FE4B2B');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB7E3C61F9');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB66AB7494');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BBE7CA843C');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB141E829E');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BBE8DE7170');
        $this->addSql('DROP INDEX IDX_795FD9BB7E3C61F9 ON attachment');
        $this->addSql('DROP INDEX IDX_795FD9BB66AB7494 ON attachment');
        $this->addSql('DROP INDEX IDX_795FD9BBE7CA843C ON attachment');
        $this->addSql('DROP INDEX IDX_795FD9BB141E829E ON attachment');
        $this->addSql('DROP INDEX IDX_795FD9BBE8DE7170 ON attachment');
        $this->addSql('ALTER TABLE attachment DROP id_owner, DROP id_company_owner, DROP description, DROP archivedBy, DROP updatedBy, CHANGE archived archived DATETIME DEFAULT NULL, CHANGE original_name original_name VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, CHANGE addedby id_client INT NOT NULL');
        $this->addSql('CREATE INDEX id_client ON attachment (id_client)');
        $this->addSql('ALTER TABLE attachment RENAME INDEX idx_795fd9bb7fe4b2b TO fk_attachment_id_type');
    }
}
