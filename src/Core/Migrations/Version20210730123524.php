<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210730123524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Hubspot] CALS-4339 synchronize companies';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_hubspot_company DROP FOREIGN KEY FK_597B016EA76ED395');
        $this->addSql('DROP INDEX UNIQ_597B016EA76ED395 ON core_hubspot_company');
        $this->addSql('ALTER TABLE core_hubspot_company ADD hubspot_company_id INT NOT NULL, DROP user_id, CHANGE company_id company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_hubspot_company ADD CONSTRAINT FK_597B016E979B1AD6 FOREIGN KEY (company_id) REFERENCES core_company (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_597B016E979B1AD6 ON core_hubspot_company (company_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_hubspot_company DROP FOREIGN KEY FK_597B016E979B1AD6');
        $this->addSql('DROP INDEX UNIQ_597B016E979B1AD6 ON core_hubspot_company');
        $this->addSql('ALTER TABLE core_hubspot_company ADD user_id INT DEFAULT NULL, DROP hubspot_company_id, CHANGE company_id company_id INT NOT NULL');
        $this->addSql('ALTER TABLE core_hubspot_company ADD CONSTRAINT FK_597B016EA76ED395 FOREIGN KEY (user_id) REFERENCES core_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_597B016EA76ED395 ON core_hubspot_company (user_id)');
    }
}
