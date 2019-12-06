<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191205104804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'FRONT-33 Add confidentiality acceptance in participation contact table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE project_confidentiality_acceptance');
        $this->addSql('ALTER TABLE project_participation_contact ADD confidentiality_accepted DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE project_confidentiality_acceptance (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, id_client INT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_1C7FA7F2F12E799E (id_project), INDEX IDX_1C7FA7F2E173B1B8 (id_client), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE project_confidentiality_acceptance ADD CONSTRAINT FK_1C7FA7F2E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_confidentiality_acceptance ADD CONSTRAINT FK_1C7FA7F2F12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation_contact DROP confidentiality_accepted');
    }
}
