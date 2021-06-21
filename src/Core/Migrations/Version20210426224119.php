<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210426224119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Fix non nullable company group tag';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project DROP CONSTRAINT FK_59B349BF4237BD1D');
        $this->addSql('ALTER TABLE agency_project CHANGE id_company_group_tag id_company_group_tag INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BF4237BD1D FOREIGN KEY (id_company_group_tag) REFERENCES core_company_group_tag (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project DROP CONSTRAINT FK_59B349BF4237BD1D');
        $this->addSql('ALTER TABLE agency_project CHANGE id_company_group_tag id_company_group_tag INT NOT NULL');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BF4237BD1D FOREIGN KEY (id_company_group_tag) REFERENCES core_company_group_tag (id)');
    }
}
