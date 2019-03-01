<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190301144711 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE project_company_black_list (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, id_company INT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_9CF0AFA6F12E799E (id_project), INDEX IDX_9CF0AFA69122A03F (id_company), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_company_white_list (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, id_company INT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_4B0D6C83F12E799E (id_project), INDEX IDX_4B0D6C839122A03F (id_company), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_company_black_list ADD CONSTRAINT FK_9CF0AFA6F12E799E FOREIGN KEY (id_project) REFERENCES projects (id_project)');
        $this->addSql('ALTER TABLE project_company_black_list ADD CONSTRAINT FK_9CF0AFA69122A03F FOREIGN KEY (id_company) REFERENCES companies (id_company)');
        $this->addSql('ALTER TABLE project_company_white_list ADD CONSTRAINT FK_4B0D6C83F12E799E FOREIGN KEY (id_project) REFERENCES projects (id_project)');
        $this->addSql('ALTER TABLE project_company_white_list ADD CONSTRAINT FK_4B0D6C839122A03F FOREIGN KEY (id_company) REFERENCES companies (id_company)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE project_company_black_list');
        $this->addSql('DROP TABLE project_company_white_list');
    }
}
