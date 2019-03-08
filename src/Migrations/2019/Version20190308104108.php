<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190308104108 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Rename table project_company_role to project_participant';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE project_participant (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, id_company INT NOT NULL, roles JSON NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_1F509CEAF12E799E (id_project), INDEX IDX_1F509CEA9122A03F (id_company), UNIQUE INDEX UNIQ_1F509CEAF12E799E9122A03F (id_project, id_company), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_participant ADD CONSTRAINT FK_1F509CEAF12E799E FOREIGN KEY (id_project) REFERENCES projects (id_project)');
        $this->addSql('ALTER TABLE project_participant ADD CONSTRAINT FK_1F509CEA9122A03F FOREIGN KEY (id_company) REFERENCES companies (id_company)');
        $this->addSql('DROP TABLE project_company_role');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE project_company_role (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, id_company INT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', roles JSON NOT NULL, INDEX IDX_3EA374E7F12E799E (id_project), UNIQUE INDEX UNIQ_3EA374E7F12E799E9122A03F (id_project, id_company), INDEX IDX_3EA374E79122A03F (id_company), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE project_company_role ADD CONSTRAINT FK_3EA374E79122A03F FOREIGN KEY (id_company) REFERENCES companies (id_company) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_company_role ADD CONSTRAINT FK_3EA374E7F12E799E FOREIGN KEY (id_project) REFERENCES projects (id_project) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE project_participant');
    }
}
