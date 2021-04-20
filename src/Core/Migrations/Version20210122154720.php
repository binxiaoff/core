<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210122154720 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3170 [Agency] Add project status';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE agency_project_status (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, added_by INT NOT NULL, status INT NOT NULL, INDEX IDX_9D3BD49EF12E799E (id_project), INDEX IDX_9D3BD49E699B6BAF (added_by), UNIQUE INDEX UNIQ_9D3BD49EF12E799E7B00651C (id_project, status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency_project_status ADD CONSTRAINT FK_9D3BD49EF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_project_status ADD CONSTRAINT FK_9D3BD49E699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id)');
        $this->addSql('ALTER TABLE agency_project ADD id_current_status INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BF41AF0274 FOREIGN KEY (id_current_status) REFERENCES agency_project_status (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_59B349BF41AF0274 ON agency_project (id_current_status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BF41AF0274');
        $this->addSql('DROP TABLE agency_project_status');
        $this->addSql('DROP INDEX UNIQ_59B349BF41AF0274 ON agency_project');
        $this->addSql('ALTER TABLE agency_project DROP id_current_status');
    }
}
