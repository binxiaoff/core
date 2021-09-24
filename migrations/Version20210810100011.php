<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210810100011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Hubspot] CALS-4339 synchronize companies';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_hubspot_company DROP FOREIGN KEY FK_597B016E979B1AD6');
        $this->addSql('DROP INDEX UNIQ_597B016E979B1AD6 ON core_hubspot_company');
        $this->addSql('ALTER TABLE core_hubspot_company ADD synchronized DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP updated, CHANGE hubspot_company_id hubspot_company_id VARCHAR(255) NOT NULL, CHANGE company_id id_company INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_hubspot_company ADD CONSTRAINT FK_597B016E9122A03F FOREIGN KEY (id_company) REFERENCES core_company (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_597B016E9122A03F ON core_hubspot_company (id_company)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_hubspot_company DROP FOREIGN KEY FK_597B016E9122A03F');
        $this->addSql('DROP INDEX UNIQ_597B016E9122A03F ON core_hubspot_company');
        $this->addSql('ALTER TABLE core_hubspot_company ADD updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP synchronized, CHANGE hubspot_company_id hubspot_company_id INT NOT NULL, CHANGE id_company company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_hubspot_company ADD CONSTRAINT FK_597B016E979B1AD6 FOREIGN KEY (company_id) REFERENCES core_company (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_597B016E979B1AD6 ON core_hubspot_company (company_id)');
    }
}
