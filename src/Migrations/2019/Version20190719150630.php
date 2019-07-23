<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190719150630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE foncaris_request (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, choice SMALLINT DEFAULT NULL, relative_file_path VARCHAR(191) DEFAULT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_BF3AEF05F12E799E (id_project), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE foncaris_request ADD CONSTRAINT FK_BF3AEF05F12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project DROP foncaris_guarantee');
        $this->addSql('ALTER TABLE acceptations_legal_docs CHANGE pdf_name relative_file_path VARCHAR(191) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE foncaris_request');
        $this->addSql('ALTER TABLE acceptations_legal_docs CHANGE relative_file_path pdf_name VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE project ADD foncaris_guarantee SMALLINT DEFAULT NULL');
    }
}
