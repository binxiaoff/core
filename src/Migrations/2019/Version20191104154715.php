<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191104154715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'TECH-176 Rename id_company attribute of Company entity to id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE companies DROP FOREIGN KEY FK_8244AA3A91C00F');
        $this->addSql('ALTER TABLE companies CHANGE id_company id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE companies ADD CONSTRAINT FK_8244AA3A91C00F FOREIGN KEY (id_parent_company) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE24FEBA6C');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE4C5E290C');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE24FEBA6C FOREIGN KEY (id_company_submitter) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE4C5E290C FOREIGN KEY (id_borrower_company) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC475499122A03F');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC475499122A03F FOREIGN KEY (id_company) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE ca_regional_bank DROP FOREIGN KEY FK_661B74A49122A03F');
        $this->addSql('ALTER TABLE ca_regional_bank ADD CONSTRAINT FK_1F07A1669122A03F FOREIGN KEY (id_company) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE project_offer DROP FOREIGN KEY FK_3A838EA08BB74F6C');
        $this->addSql('ALTER TABLE project_offer ADD CONSTRAINT FK_3A838EA08BB74F6C FOREIGN KEY (id_lender) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE company_status_history DROP FOREIGN KEY FK_1A2286D9122A03F');
        $this->addSql('ALTER TABLE company_status_history ADD CONSTRAINT FK_1A2286D9122A03F FOREIGN KEY (id_company) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB66AB7494');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB36B9957C FOREIGN KEY (id_company_owner) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE staff DROP FOREIGN KEY FK_426EF3929122A03F');
        $this->addSql('ALTER TABLE staff ADD CONSTRAINT FK_426EF3929122A03F FOREIGN KEY (id_company) REFERENCES companies (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE companies CHANGE id id_company INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE companies DROP FOREIGN KEY FK_8244AA3A91C00F');
        $this->addSql('ALTER TABLE companies ADD CONSTRAINT FK_8244AA3A91C00F FOREIGN KEY (id_parent_company) REFERENCES companies (id_company) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB36B9957C');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB66AB7494 FOREIGN KEY (id_company_owner) REFERENCES companies (id_company) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE ca_regional_bank DROP FOREIGN KEY FK_1F07A1669122A03F');
        $this->addSql('ALTER TABLE ca_regional_bank ADD CONSTRAINT FK_661B74A49122A03F FOREIGN KEY (id_company) REFERENCES companies (id_company) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE company_status_history DROP FOREIGN KEY FK_1A2286D9122A03F');
        $this->addSql('ALTER TABLE company_status_history ADD CONSTRAINT FK_1A2286D9122A03F FOREIGN KEY (id_company) REFERENCES companies (id_company) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE4C5E290C');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE24FEBA6C');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE4C5E290C FOREIGN KEY (id_borrower_company) REFERENCES companies (id_company) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE24FEBA6C FOREIGN KEY (id_company_submitter) REFERENCES companies (id_company) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_offer DROP FOREIGN KEY FK_3A838EA08BB74F6C');
        $this->addSql('ALTER TABLE project_offer ADD CONSTRAINT FK_3A838EA08BB74F6C FOREIGN KEY (id_lender) REFERENCES companies (id_company) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC475499122A03F');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC475499122A03F FOREIGN KEY (id_company) REFERENCES companies (id_company) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE staff DROP FOREIGN KEY FK_426EF3929122A03F');
        $this->addSql('ALTER TABLE staff ADD CONSTRAINT FK_426EF3929122A03F FOREIGN KEY (id_company) REFERENCES companies (id_company) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
