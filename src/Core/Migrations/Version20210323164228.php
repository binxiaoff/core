<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210323164228 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agency_project ADD id_company_group_tag INT NOT NULL');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BF4237BD1D FOREIGN KEY (id_company_group_tag) REFERENCES core_company_group_tag (id)');
        $this->addSql('CREATE INDEX IDX_59B349BF4237BD1D ON agency_project (id_company_group_tag)');
        $this->addSql('ALTER TABLE agency_zz_versioned_project CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BF4237BD1D');
        $this->addSql('DROP INDEX IDX_59B349BF4237BD1D ON agency_project');
        $this->addSql('ALTER TABLE agency_project DROP id_company_group_tag');
        $this->addSql('ALTER TABLE agency_zz_versioned_project CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
