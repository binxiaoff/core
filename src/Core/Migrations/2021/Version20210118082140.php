<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210118082140 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2114 : remove unused property company.id_parent_company';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_company DROP FOREIGN KEY FK_8244AA3A91C00F');
        $this->addSql('DROP INDEX IDX_5DA8BC7C91C00F ON core_company');
        $this->addSql('ALTER TABLE core_company DROP id_parent_company');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_company ADD id_parent_company INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_company ADD CONSTRAINT FK_8244AA3A91C00F FOREIGN KEY (id_parent_company) REFERENCES core_company (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_5DA8BC7C91C00F ON core_company (id_parent_company)');
    }
}
