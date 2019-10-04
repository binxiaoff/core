<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191001095144 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-289 Create and alter the tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE project_participation (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, id_company INT NOT NULL, added_by INT NOT NULL, roles JSON NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7FC47549F12E799E (id_project), INDEX IDX_7FC475499122A03F (id_company), INDEX IDX_7FC47549699B6BAF (added_by), UNIQUE INDEX UNIQ_7FC47549F12E799E9122A03F (id_project, id_company), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_participation_contact (id INT AUTO_INCREMENT NOT NULL, id_project_participation INT NOT NULL, id_client INT NOT NULL, added_by INT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_41530AB3AE73E249 (id_project_participation), INDEX IDX_41530AB3E173B1B8 (id_client), INDEX IDX_41530AB3699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC47549F12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC475499122A03F FOREIGN KEY (id_company) REFERENCES companies (id_company)');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC47549699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3AE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id)');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB3699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client)');
        $this->addSql('DROP TABLE project_participant');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8244AA3ADA33CDFB ON companies (email_domain)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_41530AB3E173B1B8AE73E249 ON project_participation_contact (id_client, id_project_participation)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB3AE73E249');
        $this->addSql('CREATE TABLE project_participant (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, id_company INT NOT NULL, roles JSON NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_1F509CEAF12E799E (id_project), UNIQUE INDEX UNIQ_1F509CEAF12E799E9122A03F (id_project, id_company), INDEX IDX_1F509CEA9122A03F (id_company), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE project_participant ADD CONSTRAINT FK_1F509CEA9122A03F FOREIGN KEY (id_company) REFERENCES companies (id_company) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participant ADD CONSTRAINT FK_1F509CEAF12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE project_participation');
        $this->addSql('DROP TABLE project_participation_contact');
        $this->addSql('DROP INDEX UNIQ_8244AA3ADA33CDFB ON companies');
    }
}
