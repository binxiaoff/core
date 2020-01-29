<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200129143138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-773 Add company status';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE company_status (id INT AUTO_INCREMENT NOT NULL, id_company INT NOT NULL, status SMALLINT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_company_status_id_client (id_company), INDEX idx_company_status_status (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO company_status(id_company, status, added) SELECT id, 10, NOW() FROM companies WHERE id_parent_company = 1 OR id = 1');
        $this->addSql('ALTER TABLE company_status ADD CONSTRAINT FK_469F01699122A03F FOREIGN KEY (id_company) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE companies ADD id_current_status INT DEFAULT NULL');
        $this->addSql('ALTER TABLE companies ADD CONSTRAINT FK_8244AA3A41AF0274 FOREIGN KEY (id_current_status) REFERENCES company_status (id)');
        $this->addSql('UPDATE companies c SET id_current_status = (SELECT id FROM company_status WHERE c.id = company_status.id_company)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8244AA3A41AF0274 ON companies (id_current_status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE companies DROP FOREIGN KEY FK_8244AA3A41AF0274');
        $this->addSql('DROP TABLE company_status');
        $this->addSql('DROP INDEX UNIQ_8244AA3A41AF0274 ON companies');
        $this->addSql('ALTER TABLE companies DROP id_current_status');
    }
}
