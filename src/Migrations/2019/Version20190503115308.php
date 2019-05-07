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
        return 'CALS-104 add new fields to attachment';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX id_client ON attachment');
        $this->addSql('ALTER TABLE attachment ADD id_client_owner INT DEFAULT NULL, ADD id_company_owner INT DEFAULT NULL, ADD description VARCHAR(191) DEFAULT NULL, ADD archived_by INT DEFAULT NULL, ADD updated_by INT DEFAULT NULL, CHANGE original_name original_name VARCHAR(191) NOT NULL, CHANGE archived archived DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE id_client added_by INT NOT NULL');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB7E3C61F9 FOREIGN KEY (id_client_owner) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB66AB7494 FOREIGN KEY (id_company_owner) REFERENCES companies (id_company)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBE7CA843C FOREIGN KEY (added_by) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB141E829E FOREIGN KEY (archived_by) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBE8DE7170 FOREIGN KEY (updated_by) REFERENCES clients (id_client)');
        $this->addSql('CREATE INDEX IDX_795FD9BB4FCC0FB9 ON attachment (id_client_owner)');
        $this->addSql('CREATE INDEX IDX_795FD9BB36B9957C ON attachment (id_company_owner)');
        $this->addSql('CREATE INDEX IDX_795FD9BB699B6BAF ON attachment (added_by)');
        $this->addSql('CREATE INDEX IDX_795FD9BB51B07D6D ON attachment (archived_by)');
        $this->addSql('CREATE INDEX IDX_795FD9BB16FE72E1 ON attachment (updated_by)');
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
        $this->addSql('DROP INDEX IDX_795FD9BB4FCC0FB9 ON attachment');
        $this->addSql('DROP INDEX IDX_795FD9BB36B9957C ON attachment');
        $this->addSql('DROP INDEX IDX_795FD9BB699B6BAF ON attachment');
        $this->addSql('DROP INDEX IDX_795FD9BB51B07D6D ON attachment');
        $this->addSql('DROP INDEX IDX_795FD9BB16FE72E1 ON attachment');
        $this->addSql('ALTER TABLE attachment DROP id_client_owner, DROP id_company_owner, DROP description, DROP archived_by, DROP updated_by, CHANGE archived archived DATETIME DEFAULT NULL, CHANGE original_name original_name VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, CHANGE added_by id_client INT NOT NULL');
        $this->addSql('CREATE INDEX id_client ON attachment (id_client)');
        $this->addSql('ALTER TABLE attachment RENAME INDEX idx_795fd9bb7fe4b2b TO fk_attachment_id_type');
    }
}
